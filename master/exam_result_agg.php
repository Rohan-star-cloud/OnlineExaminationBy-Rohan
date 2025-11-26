<?php
// Simple admin page to view aggregated exam results and export CSV
include('header.php');

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$export = isset($_GET['export']) ? 1 : 0;

// If export requested, stream CSV for chosen exam
if ($export && $exam_id > 0) {
    $sql = "SELECT r.*, u.user_name, u.user_image, oe.online_exam_title
            FROM user_exam_result r
            JOIN user_table u ON u.user_id = r.user_id
            LEFT JOIN online_exam_table oe ON oe.online_exam_id = r.exam_id
            WHERE r.exam_id = :exam_id
            ORDER BY r.total_mark DESC, r.percentage DESC";
    $stmt = $exam->connect->prepare($sql);
    $stmt->execute([':exam_id' => $exam_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=exam_' . $exam_id . '_results.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Exam ID','Exam Title','User ID','User Name','Attendance','Total Mark','Total Possible','Percentage','Updated On']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['exam_id'],
            $r['online_exam_title'],
            $r['user_id'],
            $r['user_name'],
            $r['attendance_status'],
            $r['total_mark'],
            $r['total_possible'],
            $r['percentage'],
            $r['updated_on']
        ]);
    }
    fclose($out);
    exit;
}

// Fetch aggregated rows for display if exam selected
$rows = [];
$exam_title = '';
if ($exam_id > 0) {
    $sql = "SELECT r.*, u.user_name, u.user_image, oe.online_exam_title
            FROM user_exam_result r
            JOIN user_table u ON u.user_id = r.user_id
            LEFT JOIN online_exam_table oe ON oe.online_exam_id = r.exam_id
            WHERE r.exam_id = :exam_id
            ORDER BY r.total_mark DESC, r.percentage DESC";
    $stmt = $exam->connect->prepare($sql);
    $stmt->execute([':exam_id' => $exam_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        $exam_title = $rows[0]['online_exam_title'];
    } else {
        // Try to get title from online_exam_table even if no results yet
        $tstmt = $exam->connect->prepare("SELECT online_exam_title FROM online_exam_table WHERE online_exam_id = :eid");
        $tstmt->execute([':eid' => $exam_id]);
        $trow = $tstmt->fetch(PDO::FETCH_ASSOC);
        if ($trow) $exam_title = $trow['online_exam_title'];
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
            <div class="col-md-4 text-right">
                <?php if ($exam_id > 0): ?>
                    <a href="exam_result_agg.php?exam_id=<?php echo $exam_id; ?>&export=1" class="btn btn-sm btn-success">Export CSV</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="form-inline mb-3" method="get">
            <label class="mr-2" for="exam_id">Select Exam:</label>
            <select name="exam_id" id="exam_id" class="form-control mr-2">
                <option value="0">-- Select --</option>
                <?php echo $exam->Fill_exam_list(); ?>
            </select>
            <button type="submit" class="btn btn-primary">Show</button>
            <?php if ($exam_id > 0): ?><a href="exam_result_agg.php" class="btn btn-link ml-2">Clear</a><?php endif; ?>
        </form>

        <?php if ($exam_id == 0): ?>
            <div class="alert alert-info">Please select an exam to view aggregated results. If you have not run the population SQL yet, results may be empty.</div>
        <?php else: ?>
            <h5><?php echo htmlspecialchars($exam_title ?: 'Exam ID: '.$exam_id); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="agg_result_table">
                    <thead>
                        <tr>
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
                        <?php foreach ($rows as $r): ?>
                            <tr>
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
        "order": [[3, 'desc']],
        "pageLength": 25
    });
    // Pre-select exam if provided
    var sel = <?php echo json_encode($exam_id); ?>;
    if (sel) {
        $('#exam_id').val(sel);
    }
});
</script>

<?php
include('footer.php');
