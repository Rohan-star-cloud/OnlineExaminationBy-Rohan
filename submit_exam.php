<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required class
include_once('master/Examination.php');
$exam = new Examination;

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$exam_id = $input['exam_id'] ?? '';
$answers = $input['answers'] ?? [];
// optional tz_name (IANA) from Intl API, or tz_offset in minutes from JS getTimezoneOffset()
$tz_name = isset($input['tz_name']) ? trim($input['tz_name']) : null;
$tz_offset = isset($input['tz_offset']) ? intval($input['tz_offset']) : null;

// Validate input
if (empty($exam_id) || empty($answers)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Scheduling-by-date removed for submissions: allow submit regardless of the scheduled date.
// (We still validate exam existence below and will proceed to grading.)
$exam->query = "SELECT online_exam_datetime FROM online_exam_table WHERE online_exam_id = :exam_id LIMIT 1";
$exam->data = array(':exam_id' => $exam_id);
$exam_info = $exam->query_result();
if (empty($exam_info)) {
    echo json_encode(['success' => false, 'message' => 'Exam not found']);
    exit;
}
// $scheduled_dt retained for informational purposes if needed later
$scheduled_dt = $exam_info[0]['online_exam_datetime'];

try {
    // Get correct answers from database
    $exam->query = "
        SELECT question_id, answer_option 
        FROM question_table 
        WHERE online_exam_id = '$exam_id'
    ";
    $correct_answers = $exam->query_result();
    
    // Calculate results
    $total_questions = count($correct_answers);
    $correct_count = 0;
    $total_marks = 0;
    
    foreach ($correct_answers as $answer) {
        $question_id = $answer['question_id'];
        $user_answer = $answers["question_$question_id"] ?? null;
        
        if ($user_answer == $answer['answer_option']) {
            $correct_count++;
            $marks = 1; // Add positive mark for correct answer
        } else {
            $marks = 0; // No negative marking
        }
        
        // Save user's answer
        $exam->query = "
            INSERT INTO user_exam_question_answer 
            (user_id, exam_id, question_id, user_answer_option, marks) 
            VALUES (
                '" . $_SESSION['user_id'] . "',
                '$exam_id',
                '$question_id',
                '" . ($user_answer ?? '') . "',
                '$marks'
            )
        ";
        $exam->execute_query();
        
        $total_marks += $marks;
    }
    
    // Calculate percentage
    $percentage = ($total_marks / $total_questions) * 100;
    
    // Update exam status
    $exam->query = "
        UPDATE online_exam_table 
        SET online_exam_status = 'Completed'
        WHERE online_exam_id = '$exam_id'
    ";
    $exam->execute_query();

    // Update or insert aggregated results in user_exam_result table
    $exam->query = "
        INSERT INTO user_exam_result 
        (user_id, exam_id, marks_obtained, total_marks, exam_status)
        VALUES (
            :user_id,
            :exam_id,
            :marks_obtained,
            :total_marks,
            'Completed'
        )
        ON DUPLICATE KEY UPDATE
            marks_obtained = VALUES(marks_obtained),
            total_marks = VALUES(total_marks),
            exam_status = VALUES(exam_status)
    ";
    $exam->data = array(
        ':user_id' => $_SESSION['user_id'],
        ':exam_id' => $exam_id,
        ':marks_obtained' => $total_marks,
        ':total_marks' => $total_questions
    );
    $exam->execute_query();
    
    // Prepare results
    $results = [
        'total' => $total_questions,
        'correct' => $correct_count,
        'marks' => $total_marks,
        'percentage' => round($percentage, 2)
    ];
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing exam: ' . $e->getMessage()
    ]);
}
?>