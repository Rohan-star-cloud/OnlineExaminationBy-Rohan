<?php
include_once 'header.php';
include_once('master/Examination.php');
$exam = new Examination;

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// exam id and optional timezone info (tz_name preferred)
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 1;
$tz_name = isset($_GET['tz_name']) ? trim($_GET['tz_name']) : null;
$tz_offset = isset($_GET['tz_offset']) ? intval($_GET['tz_offset']) : null;

// Fetch exam schedule and validate current date matches exam date
// Use a direct PDO query here to avoid issues with the Examination helper parameter state.
try {
    $pdo = new PDO("mysql:host=localhost;dbname=online_examination", "root", "");
    $stmt = $pdo->prepare("SELECT online_exam_datetime, online_exam_duration, online_exam_status FROM online_exam_table WHERE online_exam_id = :exam_id LIMIT 1");
    $stmt->bindValue(':exam_id', $exam_id, PDO::PARAM_INT);
    $stmt->execute();
    $exam_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fall back to using the Examination helper if direct PDO fails
    $exam->query = "SELECT online_exam_datetime, online_exam_duration, online_exam_status FROM online_exam_table WHERE online_exam_id = :exam_id LIMIT 1";
    $exam->data = array(':exam_id' => $exam_id);
    $exam_result = $exam->query_result();
}

$questions = array();
$allow_start = false;
$scheduled_date = '';
// Permit starting the exam regardless of the scheduled date. Keep scheduled_date for display.
if (!empty($exam_result)) {
    $scheduled = $exam_result[0]['online_exam_datetime'];
    $scheduled_date = date('Y-m-d', strtotime($scheduled));
    // Only prevent start if exam explicitly marked inactive in DB (if such a status exists)
    $exam_status = isset($exam_result[0]['online_exam_status']) ? $exam_result[0]['online_exam_status'] : '';
    if (strtolower($exam_status) !== 'inactive') {
        $allow_start = true;
    }
}

if ($allow_start) {
    // Fetch unique questions for this exam using direct PDO to avoid parameter binding issues
    try {
        $qstmt = $pdo->prepare("SELECT DISTINCT question_id, question_title FROM question_table WHERE online_exam_id = :exam_id ORDER BY question_id ASC");
        $qstmt->bindValue(':exam_id', $exam_id, PDO::PARAM_INT);
        $qstmt->execute();
        $questions = $qstmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback to Examination helper
        $exam->query = "
            SELECT DISTINCT question_id, question_title 
            FROM question_table 
            WHERE online_exam_id = $exam_id
            ORDER BY question_id ASC
        ";
        $questions = $exam->query_result();
    }
}
?>

<?php if ($allow_start && !empty($questions)): ?>
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Online Examination</h3>
            <div class="d-flex align-items-center gap-3">
                <div id="timerDisplay" class="alert alert-light mb-0 px-3 py-2" style="min-width: 150px; text-align: center; border-radius: 5px;">
                    <strong id="timerText" style="font-size: 1.1em; color: #333;">00:00:00</strong>
                    <div style="font-size: 0.85em; color: #666;">Time Remaining</div>
                </div>
                <button type="button" id="shuffleBtn" class="btn btn-warning me-2">
                    Shuffle Questions
                </button>
                <button type="button" id="resetOrderBtn" class="btn btn-secondary" disabled>
                    Reset Order
                </button>
            </div>
        </div>
        <div class="card-body">
            <form action="exam_result.php" method="POST" id="examForm">
                <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam_id); ?>">
                <input type="hidden" id="examDuration" value="<?php echo isset($exam_result[0]['online_exam_duration']) ? intval($exam_result[0]['online_exam_duration']) : 60; ?>">

                <div id="questionsContainer">
                    <?php
                    foreach ($questions as $q) {
                        echo '<div class="mb-4 p-3 border rounded bg-light question-card" data-qid="'.$q['question_id'].'">';
                        echo '<h5>' . htmlspecialchars($q['question_title']) . '</h5>';

                        // Fetch options via PDO to avoid Examination helper parameter issues
                        try {
                            $optStmt = $pdo->prepare("SELECT DISTINCT option_number, option_title FROM option_table WHERE question_id = :qid ORDER BY option_number ASC");
                            $optStmt->bindValue(':qid', intval($q['question_id']), PDO::PARAM_INT);
                            $optStmt->execute();
                            $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $exam->query = "
                                SELECT DISTINCT option_number, option_title 
                                FROM option_table 
                                WHERE question_id = " . intval($q['question_id']) . "
                                ORDER BY option_number ASC
                            ";
                            $options = $exam->query_result();
                        }

                        foreach ($options as $opt) {
                            echo '
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="question_' . $q['question_id'] . '" 
                                       id="q' . $q['question_id'] . '_opt' . $opt['option_number'] . '" 
                                       value="' . $opt['option_number'] . '" required>
                                <label class="form-check-label" for="q' . $q['question_id'] . '_opt' . $opt['option_number'] . '">
                                    ' . htmlspecialchars($opt['option_title']) . '
                                </label>
                            </div>';
                        }

                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-4 py-2">
                        Submit Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary">Exam Not Available</h3>
                <?php if ($scheduled_date): ?>
                    <p class="lead">This exam is scheduled on <strong><?php echo htmlspecialchars(date('F j, Y', strtotime($scheduled_date))); ?></strong>.</p>
                <?php else: ?>
                    <p class="lead">Exam schedule not found. Please contact the administrator.</p>
                <?php endif; ?>
                <a href="view_exam.php?exam_id=<?php echo urlencode($exam_id); ?>" class="btn btn-outline-primary mt-3">View Exam Details</a>
                <a href="enroll_exam.php" class="btn btn-secondary mt-3 ms-2">Back to Exams</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
body { background: #f7f9fb; }
.card { border-radius: 10px; }
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
/* Shuffle animation styles */
.question-card {
    transition: transform 300ms ease, opacity 300ms ease;
}
.question-card.reorder-anim {
    transform: translateY(10px) scale(0.98);
    opacity: 0.9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Timer functionality
    const examDurationMinutes = parseInt(document.getElementById('examDuration').value) || 60;
    const examDurationSeconds = examDurationMinutes * 60;
    let timeRemaining = examDurationSeconds;
    const timerDisplay = document.getElementById('timerText');
    const timerContainer = document.getElementById('timerDisplay');
    const warningThreshold = 5 * 60; // 5 minutes in seconds
    let warningShown = false;

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function updateTimer() {
        timerDisplay.textContent = formatTime(timeRemaining);

        // 5-minute warning
        if (timeRemaining <= warningThreshold && !warningShown) {
            warningShown = true;
            timerContainer.classList.remove('alert-light');
            timerContainer.classList.add('alert-warning');
            alert('⏰ Warning: Only 5 minutes remaining! Please complete your exam.');
        }

        // Time's up
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerContainer.classList.remove('alert-light', 'alert-warning');
            timerContainer.classList.add('alert-danger');
            timerDisplay.textContent = 'TIME UP!';
            alert('⏱️ Time is up! Your exam will be submitted automatically.');
            // Auto-submit the form
            document.getElementById('examForm').submit();
            return;
        }

        timeRemaining--;
    }

    // Update timer immediately and then every second
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    const shuffleBtn = document.getElementById('shuffleBtn');
    const container = document.getElementById('questionsContainer');

    function shuffleArray(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
    }

    if (shuffleBtn && container) {
        const resetBtn = document.getElementById('resetOrderBtn');
        // capture original order of question-card elements (references)
        const originalOrder = Array.from(container.querySelectorAll('.question-card'));
        let animating = false;

        function applyReorderAnim(cards) {
            cards.forEach(c => c.classList.add('reorder-anim'));
            // ensure the animation class is applied before DOM changes
            return new Promise(resolve => setTimeout(resolve, 50));
        }

        function clearReorderAnim(cards) {
            // trigger reflow then remove class to animate to normal
            window.requestAnimationFrame(() => {
                cards.forEach(c => c.classList.remove('reorder-anim'));
            });
        }

        shuffleBtn.addEventListener('click', async function() {
            if (animating) return;
            const currentCards = Array.from(container.querySelectorAll('.question-card'));
            if (currentCards.length <= 1) return;

            animating = true;
            shuffleBtn.disabled = true;

            // Shuffle the current nodes so selections remain attached
            const shuffled = currentCards.slice();
            shuffleArray(shuffled);

            await applyReorderAnim(currentCards);

            container.innerHTML = '';
            shuffled.forEach(card => container.appendChild(card));

            clearReorderAnim(shuffled);

            // Update numbering
            container.querySelectorAll('.question-card').forEach((qCard, index) => {
                const h5 = qCard.querySelector('h5');
                if (!h5) return;
                const text = h5.textContent.replace(/^\d+\.\s*/, '');
                h5.textContent = (index + 1) + '. ' + text;
            });

            // enable reset button
            if (resetBtn) resetBtn.disabled = false;

            shuffleBtn.disabled = false;
            animating = false;
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', async function() {
                if (animating) return;
                const currentCards = Array.from(container.querySelectorAll('.question-card'));
                if (currentCards.length <= 1) return;

                animating = true;
                resetBtn.disabled = true;
                shuffleBtn.disabled = true;

                await applyReorderAnim(currentCards);

                container.innerHTML = '';
                originalOrder.forEach(card => container.appendChild(card));

                clearReorderAnim(originalOrder);

                // Update numbering
                container.querySelectorAll('.question-card').forEach((qCard, index) => {
                    const h5 = qCard.querySelector('h5');
                    if (!h5) return;
                    const text = h5.textContent.replace(/^\d+\.\s*/, '');
                    h5.textContent = (index + 1) + '. ' + text;
                });

                shuffleBtn.disabled = false;
                animating = false;
            });
        }
    }
});
</script>
