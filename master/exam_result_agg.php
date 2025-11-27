<?php
// Admin page to view aggregated exam results
include('header.php');

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// If no exam_id provided, fetch the first available exam
if ($exam_id == 0) {
    $estmt = $exam->connect->prepare("SELECT online_exam_id FROM online_exam_table LIMIT 1");
    $estmt->execute();
    $first_exam = $estmt->fetchColumn();
    if ($first_exam) {
        $exam_id = intval($first_exam);
    }
}

// Fetch aggregated rows for display if exam selected
$rows = [];
$exam_title = '';
if ($exam_id > 0) {
    // We'll compute aggregates in PHP (same algorithm as CSV) so the admin sees detailed per-user info.
    $tstmt = $exam->connect->prepare("SELECT online_exam_title, marks_per_right_answer FROM online_exam_table WHERE online_exam_id = :eid");
    $tstmt->execute([':eid' => $exam_id]);
    $tmeta = $tstmt->fetch(PDO::FETCH_ASSOC);
    $exam_title = $tmeta['online_exam_title'] ?? '';
    $marks_per_right = floatval($tmeta['marks_per_right_answer'] ?? 0);

    // Collect users who answered or enrolled
    $ustmt = $exam->connect->prepare("SELECT DISTINCT user_id FROM user_exam_question_answer WHERE exam_id = :eid");
    $ustmt->execute([':eid' => $exam_id]);
    $user_rows = $ustmt->fetchAll(PDO::FETCH_COLUMN);
    $est = $exam->connect->prepare("SELECT user_id, attendance_status FROM user_exam_enroll_table WHERE exam_id = :eid");
    $est->execute([':eid' => $exam_id]);
    $enrolled_rows = $est->fetchAll(PDO::FETCH_ASSOC);
    $enrolled = array_column($enrolled_rows, 'user_id');

    $user_ids = array_values(array_unique(array_merge($user_rows ?: [], $enrolled ?: [])));

    // Precompute total possible
    $qstmt = $exam->connect->prepare("SELECT COUNT(*) FROM question_table WHERE online_exam_id = :eid");
    $qstmt->execute([':eid' => $exam_id]);
    $total_questions = intval($qstmt->fetchColumn() ?? 0);
    $total_possible = $total_questions * $marks_per_right;

    foreach ($user_ids as $uid) {
        $ust = $exam->connect->prepare("SELECT user_name, user_image FROM user_table WHERE user_id = :uid");
        $ust->execute([':uid' => $uid]);
        $u = $ust->fetch(PDO::FETCH_ASSOC) ?: ['user_name' => 'User ' . $uid, 'user_image' => ''];

        $mstmt = $exam->connect->prepare("SELECT IFNULL(SUM(CAST(marks AS DECIMAL(10,2))),0) FROM user_exam_question_answer WHERE exam_id = :eid AND user_id = :uid");
        $mstmt->execute([':eid' => $exam_id, ':uid' => $uid]);
        $total_mark = floatval($mstmt->fetchColumn() ?? 0);

        // attendance: prefer enrollment table value if present
        $attendance = 'Absent';
        foreach ($enrolled_rows as $er) {
            if ($er['user_id'] == $uid) { $attendance = $er['attendance_status'] ?: 'Present'; break; }
        }

        $percentage = $total_possible > 0 ? round(($total_mark / $total_possible) * 100, 2) : 0;

        $updated_on = '';
        try {
            $rst = $exam->connect->prepare("SELECT updated_on FROM user_exam_result WHERE exam_id = :eid AND user_id = :uid LIMIT 1");
            $rst->execute([':eid' => $exam_id, ':uid' => $uid]);
            $rrow = $rst->fetch(PDO::FETCH_ASSOC);
            if ($rrow && !empty($rrow['updated_on'])) $updated_on = $rrow['updated_on'];
        } catch (Exception $ex) {
            // ignore
        }

        $rows[] = [
            'exam_id' => $exam_id,
            'online_exam_title' => $exam_title,
            'user_id' => $uid,
            'user_name' => $u['user_name'],
            'user_image' => $u['user_image'],
            'attendance_status' => $attendance,
            'total_mark' => $total_mark,
            'total_possible' => $total_possible,
            'percentage' => $percentage,
            'updated_on' => $updated_on
        ];
    }

    // ========== Borda Count Rank Aggregation Algorithm ==========
    // 
    // PURPOSE:
    // Rank students fairly by combining two independent metrics:
    // 1. Exam Performance (percentage scored)
    // 2. Attendance (Present vs Absent)
    //
    // WHY BORDA COUNT?
    // - Prevents single factor from dominating (e.g., high score but absent)
    // - Balances academic performance with participation
    // - Handles ties naturally (same score = same rank)
    //
    // ALGORITHM:
    // 1. Create separate rankings for each metric
    // 2. Assign points based on rank position: position 1 = n points, position 2 = n-1, ..., position n = 1
    // 3. Sum points across all metrics
    // 4. Final ranking by total Borda score (highest first)
    //
    // EXAMPLE (4 students):
    //   Student | % | Attendance | Rank_% | Pts_% | Rank_Att | Pts_Att | Borda | Final_Rank
    //   --------|---|------------|--------|-------|----------|---------|-------|----------
    //   A       |95%| Present    |   1    |   4   |    1     |    4    |   8   |    1
    //   B       |85%| Present    |   2    |   3   |    1     |    4    |   7   |    2
    //   C       |75%| Absent     |   3    |   2   |    3     |    2    |   4   |    3
    //   D       |65%| Absent     |   4    |   1   |    3     |    2    |   3   |    4
    //
    if (count($rows) > 0) {

        // ===== RANKING 1: By Percentage (Descending) =====
        $percentage_sorted = $rows;
        usort($percentage_sorted, function($a, $b) {
            return $b['percentage'] <=> $a['percentage']; // descending: highest % first
        });

        // Assign Borda points: position 1 gets n points, position 2 gets n-1, etc.
        $n = count($percentage_sorted);
        $percentage_points = [];
        foreach ($percentage_sorted as $rank => $user) {
            $percentage_points[$user['user_id']] = $n - $rank;
        }

        // ===== RANKING 2: By Attendance (Present > Absent) =====
        $attendance_sorted = $rows;
        usort($attendance_sorted, function($a, $b) {
            // Treat 'present' as 1, 'absent' as 2 so present comes first
            $valA = strtolower($a['attendance_status']) === 'present' ? 1 : 2;
            $valB = strtolower($b['attendance_status']) === 'present' ? 1 : 2;
            return $valA <=> $valB; // ascending: present first
        });

        // Assign Borda points for attendance
        $attendance_points = [];
        foreach ($attendance_sorted as $rank => $user) {
            $attendance_points[$user['user_id']] = $n - $rank;
        }

        // ===== STEP 3: Sum Borda Points =====
        foreach ($rows as &$row) {
            $uid = $row['user_id'];
            $p_points = $percentage_points[$uid] ?? 0;
            $a_points = $attendance_points[$uid] ?? 0;
            $row['borda_score'] = $p_points + $a_points;
        }
        unset($row); // break reference

        // ===== STEP 4: Final Ranking by Borda Score =====
        usort($rows, function($a, $b) {
            return $b['borda_score'] <=> $a['borda_score']; // descending: highest score first
        });
    }
}
?>

<br />
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="exam.php">Exam List</a></li>
        <li class="breadcrumb-item active" aria-current="page">Aggregated Results</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <h3 class="panel-title">Aggregated Exam Results</h3>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($exam_id > 0): ?>
            <h5><?php echo htmlspecialchars($exam_title ?: 'Exam ID: '.$exam_id); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="agg_result_table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Borda Score</th>
                            <th>Image</th>
                            <th>User Name</th>
                            <th>Attendance</th>
                            <th>Total Mark</th>
                            <th>Total Possible</th>
                            <th>Percentage</th>
                            <th>Updated On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 0;
                        $prev_score = null;
                        $skip_rank = 1;
                        foreach ($rows as $r):
                            if ($prev_score !== $r['borda_score']) {
                                $rank += $skip_rank;
                                $skip_rank = 1;
                            } else {
                                $skip_rank++;
                            }
                            $prev_score = $r['borda_score'];
                        ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td><?php echo htmlspecialchars($r['borda_score']); ?></td>
                                <td style="width:60px; text-align:center;">
                                    <?php if (!empty($r['user_image'])): ?>
                                        <img src="../upload/<?php echo htmlspecialchars($r['user_image']); ?>" alt="" style="width:40px; height:40px; object-fit:cover; border-radius:50%;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($r['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['attendance_status']); ?></td>
                                <td><?php echo htmlspecialchars($r['total_mark']); ?></td>
                                <td><?php echo htmlspecialchars($r['total_possible']); ?></td>
                                <td><?php echo htmlspecialchars($r['percentage']); ?>%</td>
                                <td><?php echo htmlspecialchars($r['updated_on']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#agg_result_table').DataTable({
        "order": [[0, 'asc']], // order by Rank ascending
        "pageLength": 25
    });
});
</script>

<?php
include('footer.php');
