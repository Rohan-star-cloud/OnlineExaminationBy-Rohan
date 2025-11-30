<?php
// ajax_action.php

include('Examination.php');

require_once('../class/class.phpmailer.php');

$exam = new Examination;

// Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Current datetime (fixed)
$current_datetime = date("Y-m-d H:i:s");

// Helper: safe get POST
function post($key, $default = null) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

// Main router
if (isset($_POST['page'])) {

    $page = $_POST['page'];

    // ---------------------------
    // Register page
    // ---------------------------
    if ($page === 'register') {

        if (post('action') == 'check_email') {
            $email = trim(post('email', ''));

            $exam->query = "
                SELECT * FROM admin_table
                WHERE admin_email_address = '" . addslashes($email) . "'
            ";

            $total_row = $exam->total_row();

            if ($total_row == 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Email already exists']);
            }
            exit;
        }

        if (post('action') == 'register') {
            $admin_verification_code = md5(rand());
            $receiver_email = post('admin_email_address', '');

            $exam->data = array(
                ':admin_email_address'      =>  $receiver_email,
                ':admin_password'           =>  password_hash(post('admin_password', ''), PASSWORD_DEFAULT),
                ':admin_verfication_code'   =>  $admin_verification_code,
                ':admin_type'               =>  'sub_master',
                ':admin_created_on'         =>  $current_datetime
            );

            $exam->query = "
                INSERT INTO admin_table
                (admin_email_address, admin_password, admin_verfication_code, admin_type, admin_created_on)
                VALUES
                (:admin_email_address, :admin_password, :admin_verfication_code, :admin_type, :admin_created_on)
            ";

            try {
                $exam->execute_query();

                $subject = 'Online Examination Registration Verification';
                $body = '
                    <p>Thank you for registering.</p>
                    <p>Please verify your email by clicking this <a href="'.$exam->home_page.'verify_email.php?type=master&code='.$admin_verification_code.'" target="_blank"><b>link</b></a>.</p>
                    <p>Thank you,</p>
                    <p>Online Examination System</p>
                ';

                $exam->send_email($receiver_email, $subject, $body);

                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
            }
            exit;
        }
    }

    // ---------------------------
    // Login page
    // ---------------------------
    if ($page === 'login') {

        if (post('action') == 'login') {
            $exam->data = array(
                ':admin_email_address' => post('admin_email_address', '')
            );

            $exam->query = "
                SELECT * FROM admin_table
                WHERE admin_email_address = :admin_email_address
            ";

            $result = $exam->query_result();

            if (count($result) > 0) {
                $output = ['error' => '']; // default
                foreach ($result as $row) {
                    if (isset($row['email_verified']) && $row['email_verified'] == 'yes') {
                        if (password_verify(post('admin_password', ''), $row['admin_password'])) {
                            $_SESSION['admin_id'] = $row['admin_id'];
                            $output = ['success' => true];
                        } else {
                            $output = ['error' => 'Wrong Password'];
                        }
                    } else {
                        $output = ['error' => 'Your Email is not verified'];
                    }
                }
            } else {
                $output = ['error' => 'Wrong Email Address'];
            }

            echo json_encode($output);
            exit;
        }
    }

    // ---------------------------
    // Exam page
    // ---------------------------
    if ($page === 'exam') {

        // ---------- fetch for DataTables ----------
        if (post('action') == 'fetch') {
            // DataTables params
            $draw = intval(post('draw', 0));
            $start = intval(post('start', 0));
            $length = intval(post('length', 10));
            $search_value = trim(post('search')['value'] ?? '');
            $order_column_index = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : null;
            $order_dir = isset($_POST['order'][0]['dir']) && in_array(strtoupper($_POST['order'][0]['dir']), ['ASC','DESC']) ? strtoupper($_POST['order'][0]['dir']) : 'DESC';

            // Base query
            $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
            $exam->query = "SELECT * FROM online_exam_table WHERE admin_id = '" . addslashes($admin_id) . "' ";

            // Search
            if ($search_value !== '') {
                $sv = addslashes($search_value);
                $exam->query .= " AND (online_exam_title LIKE '%{$sv}%' ";
                $exam->query .= " OR online_exam_datetime LIKE '%{$sv}%' ";
                $exam->query .= " OR online_exam_duration LIKE '%{$sv}%' ";
                $exam->query .= " OR total_question LIKE '%{$sv}%' ";
                $exam->query .= " OR marks_per_right_answer LIKE '%{$sv}%' ";
                $exam->query .= " OR marks_per_wrong_answer LIKE '%{$sv}%' ";
                $exam->query .= " OR online_exam_status LIKE '%{$sv}%') ";
            }

            // Order mapping
            $columns = array(
                0 => 'online_exam_title',
                1 => 'online_exam_datetime',
                2 => 'online_exam_duration',
                3 => 'total_question',
                4 => 'marks_per_right_answer',
                5 => 'marks_per_wrong_answer',
                6 => 'online_exam_status'
            );

            if ($order_column_index !== null && isset($columns[$order_column_index])) {
                $exam->query .= ' ORDER BY ' . $columns[$order_column_index] . ' ' . $order_dir . ' ';
            } else {
                $exam->query .= ' ORDER BY online_exam_id DESC ';
            }

            // Count filtered rows
            $filtered_rows = $exam->total_row();

            // Limit
            if ($length != -1) {
                $exam->query .= ' LIMIT ' . intval($start) . ', ' . intval($length);
            }

            $result = $exam->query_result();

            // Total rows (without filters)
            $exam->query = "SELECT * FROM online_exam_table WHERE admin_id = '" . addslashes($admin_id) . "'";
            $total_rows = $exam->total_row();

            $data = [];
            foreach ($result as $row) {
                $sub_array = [];
                $sub_array[] = html_entity_decode($row['online_exam_title']);
                $sub_array[] = $row['online_exam_datetime'];
                $sub_array[] = $row['online_exam_duration'] . ' Minute';
                $sub_array[] = $row['total_question'] . ' Question';
                $sub_array[] = $row['marks_per_right_answer'] . ' Mark';
                $sub_array[] = '-' . $row['marks_per_wrong_answer'] . ' Mark';

                // Status badges
                $status = '';
                switch ($row['online_exam_status']) {
                    case 'Pending':
                        $status = '<span class="badge badge-warning">Pending</span>';
                        break;
                    case 'Created':
                        $status = '<span class="badge badge-success">Created</span>';
                        break;
                    case 'Started':
                        $status = '<span class="badge badge-primary">Started</span>';
                        break;
                    case 'Completed':
                        $status = '<span class="badge badge-dark">Completed</span>';
                        break;
                    default:
                        $status = '<span class="badge badge-secondary">'.htmlspecialchars($row['online_exam_status']).'</span>';
                }

                // Buttons
                $edit_button = '';
                $delete_button = '';
                $result_button = '';

                if ($exam->Is_exam_is_not_started($row["online_exam_id"])) {
                    $edit_button = '<button type="button" name="edit" class="btn btn-primary btn-sm edit" id="'.$row['online_exam_id'].'">Edit</button>';
                    $delete_button = '<button type="button" name="delete" class="btn btn-danger btn-sm delete" id="'.$row['online_exam_id'].'">Delete</button>';
                } else {
                    $result_button = '<a href="exam_result.php?code='.$row["online_exam_code"].'" class="btn btn-dark btn-sm">Result</a>';
                }

                // Questions: allow Add and View always (as requested originally)
                $question_button = '
                    <button type="button" name="add_question" class="btn btn-info btn-sm add_question" id="'.$row['online_exam_id'].'">Add Question</button>
                    <a href="question.php?code='.$row['online_exam_code'].'" class="btn btn-warning btn-sm ms-1">View Question</a>
                ';

                $sub_array[] = $status;
                $sub_array[] = $question_button;
                $sub_array[] = $result_button;
                $sub_array[] = $edit_button . ' ' . $delete_button;

                $data[] = $sub_array;
            }

            $output = array(
                "draw" => $draw,
                "recordsTotal" => $total_rows,
                "recordsFiltered" => $filtered_rows,
                "data" => $data
            );

            echo json_encode($output);
            exit;
        }

        // ---------- Add Exam ----------
        if (post('action') == 'Add') {
            try {
                $title = $exam->clean_data(post('online_exam_title', ''));
                $datetime = post('online_exam_datetime', '') ;
                // If user passed without seconds, attempt to append :00 (if not already included)
                if ($datetime !== '' && substr($datetime, -3) !== ':00' && strlen($datetime) <= 19) {
                    $datetime = $datetime . ':00';
                }
                $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;

                $exam->data = array(
                    ':admin_id'                 =>  $admin_id,
                    ':online_exam_title'        =>  $title,
                    ':online_exam_datetime'     =>  $datetime,
                    ':online_exam_duration'     =>  post('online_exam_duration', ''),
                    ':total_question'           =>  post('total_question', ''),
                    ':marks_per_right_answer'   =>  post('marks_per_right_answer', ''),
                    ':marks_per_wrong_answer'   =>  post('marks_per_wrong_answer', ''),
                    ':online_exam_created_on'   =>  $current_datetime,
                    ':online_exam_status'       =>  'Pending',
                    ':online_exam_code'         =>  md5(rand())
                );

                $exam->query = "
                    INSERT INTO online_exam_table
                    (admin_id, online_exam_title, online_exam_datetime, online_exam_duration, total_question, marks_per_right_answer, marks_per_wrong_answer, online_exam_created_on, online_exam_status, online_exam_code)
                    VALUES (:admin_id, :online_exam_title, :online_exam_datetime, :online_exam_duration, :total_question, :marks_per_right_answer, :marks_per_wrong_answer, :online_exam_created_on, :online_exam_status, :online_exam_code)
                ";

                $exam->execute_query();

                echo json_encode(['success' => 'New Exam Details Added']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to add exam: ' . $e->getMessage()]);
            }
            exit;
        }

        // ---------- edit_fetch ----------
        if (post('action') == 'edit_fetch') {
            $exam_id = post('exam_id', '');
            $exam->query = "
                SELECT * FROM online_exam_table
                WHERE online_exam_id = '" . addslashes($exam_id) . "'
            ";

            $result = $exam->query_result();
            $output = [];
            if (count($result) > 0) {
                $row = $result[0];
                $output['online_exam_title'] = $row['online_exam_title'];
                $output['online_exam_datetime'] = $row['online_exam_datetime'];
                $output['online_exam_duration'] = $row['online_exam_duration'];
                $output['total_question'] = $row['total_question'];
                $output['marks_per_right_answer'] = $row['marks_per_right_answer'];
                $output['marks_per_wrong_answer'] = $row['marks_per_wrong_answer'];
            }
            echo json_encode($output);
            exit;
        }

        // ---------- Edit ----------
        if (post('action') == 'Edit') {
            $exam->data = array(
                ':online_exam_title'        =>  post('online_exam_title', ''),
                ':online_exam_datetime'     =>  post('online_exam_datetime', '') . ':00',
                ':online_exam_duration'     =>  post('online_exam_duration', ''),
                ':total_question'           =>  post('total_question', ''),
                ':marks_per_right_answer'   =>  post('marks_per_right_answer', ''),
                ':marks_per_wrong_answer'   =>  post('marks_per_wrong_answer', ''),
                ':online_exam_id'           =>  post('online_exam_id', '')
            );

            $exam->query = "
                UPDATE online_exam_table
                SET online_exam_title = :online_exam_title,
                    online_exam_datetime = :online_exam_datetime,
                    online_exam_duration = :online_exam_duration,
                    total_question = :total_question,
                    marks_per_right_answer = :marks_per_right_answer,
                    marks_per_wrong_answer = :marks_per_wrong_answer
                WHERE online_exam_id = :online_exam_id
            ";

            try {
                $exam->execute_query();
                echo json_encode(['success' => 'Exam Details has been changed']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to update exam: ' . $e->getMessage()]);
            }
            exit;
        }

        // ---------- Delete ----------
        if (post('action') == 'delete') {
            $exam->data = array(':online_exam_id' => post('exam_id', ''));
            $exam->query = "
                DELETE FROM online_exam_table
                WHERE online_exam_id = :online_exam_id
            ";

            try {
                $exam->execute_query();
                echo json_encode(['success' => 'Exam Details has been removed']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to remove exam: ' . $e->getMessage()]);
            }
            exit;
        }
    } // end page == 'exam'

    // ---------------------------
    // Question page
    // ---------------------------
    if ($page === 'question') {

        // Add question
        if (post('action') == 'Add') {
            try {
                $exam->data = array(
                    ':online_exam_id'   => post('online_exam_id', ''),
                    ':question_title'   => $exam->clean_data(post('question_title', '')),
                    ':answer_option'    => post('answer_option', '')
                );

                $exam->query = "
                    INSERT INTO question_table
                    (online_exam_id, question_title, answer_option)
                    VALUES (:online_exam_id, :question_title, :answer_option)
                ";

                // Get last inserted question id (your existing helper)
                $question_id = $exam->execute_question_with_last_id($exam->data);

                for ($count = 1; $count <= 4; $count++) {
                    $exam->data = array(
                        ':question_id'  =>  $question_id,
                        ':option_number'=>  $count,
                        ':option_title' =>  $exam->clean_data(post('option_title_' . $count, ''))
                    );

                    $exam->query = "
                        INSERT INTO option_table
                        (question_id, option_number, option_title)
                        VALUES (:question_id, :option_number, :option_title)
                    ";
                    $exam->execute_query($exam->data);
                }

                echo json_encode(['success' => 'Question Added']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to add question: ' . $e->getMessage()]);
            }
            exit;
        }

        // Fetch questions (DataTables)
        if (post('action') == 'fetch') {
            $exam_id = '';
            if (isset($_POST['code'])) {
                $exam_id = $exam->Get_exam_id($_POST['code']);
            } else if (isset($_POST['exam_id'])) {
                $exam_id = post('exam_id', '');
            }

            // Build base query safely
            $search_value = trim($_POST['search']['value'] ?? '');
            if ($search_value !== '') {
                $sv = addslashes($search_value);
                $exam->query = "
                    SELECT * FROM question_table
                    WHERE online_exam_id = '". addslashes($exam_id) . "'
                    AND question_title LIKE '%{$sv}%'
                ";
            } else {
                $exam->query = "
                    SELECT * FROM question_table
                    WHERE online_exam_id = '". addslashes($exam_id) . "'
                ";
            }

            // Ordering
            if (isset($_POST['order'])) {
                // Note: column index mapping could be improved, keeping existing behavior
                $col = intval($_POST['order'][0]['column']);
                $dir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
                $exam->query .= " ORDER BY question_id {$dir} ";
            } else {
                $exam->query .= " ORDER BY question_id ASC ";
            }

            // limit
            $extra_query = '';
            if (isset($_POST['length']) && $_POST['length'] != -1) {
                $extra_query .= ' LIMIT ' . intval($_POST['start']) . ', ' . intval($_POST['length']);
            }

            $filtered_rows = $exam->total_row();

            $exam->query .= $extra_query;

            $result = $exam->query_result();

            // total rows
            $exam->query = "
                SELECT * FROM question_table
                WHERE online_exam_id = '". addslashes($exam_id) . "'
            ";
            $total_rows = $exam->total_row();

            $data = [];
            foreach ($result as $row) {
                $sub_array = [];
                $sub_array[] = $row['question_title'];
                $sub_array[] = 'Option ' . $row['answer_option'];

                $edit_button = '';
                $delete_button = '';
                if ($exam->Is_exam_is_not_started($exam_id)) {
                    $edit_button = '<button type="button" name="edit" class="btn btn-primary btn-sm edit" id="'.$row['question_id'].'">Edit</button>';
                    $delete_button = '<button type="button" name="delete" class="btn btn-danger btn-sm delete" id="'.$row['question_id'].'">Delete</button>';
                }

                $sub_array[] = $edit_button . ' ' . $delete_button;
                $data[] = $sub_array;
            }

            $output = array(
                "draw" => intval($_POST["draw"] ?? 0),
                "recordsTotal" => $total_rows,
                "recordsFiltered" => $filtered_rows,
                "data" => $data
            );

            echo json_encode($output);
            exit;
        }

        // edit_fetch
        if (post('action') == 'edit_fetch') {
            $question_id = post('question_id', '');
            $exam->query = "
                SELECT * FROM question_table
                WHERE question_id = '" . addslashes($question_id) . "'
            ";
            $result = $exam->query_result();

            $output = [];
            if (count($result) > 0) {
                $row = $result[0];
                $output['question_title'] = html_entity_decode($row['question_title']);
                $output['answer_option'] = $row['answer_option'];

                for ($count = 1; $count <= 4; $count++) {
                    $exam->query = "
                        SELECT option_title FROM option_table
                        WHERE question_id = '" . addslashes($question_id) . "'
                        AND option_number = '" . intval($count) . "'
                    ";
                    $sub_result = $exam->query_result();
                    if (count($sub_result) > 0) {
                        $output["option_title_" . $count] = html_entity_decode($sub_result[0]["option_title"]);
                    } else {
                        $output["option_title_" . $count] = '';
                    }
                }
            }

            echo json_encode($output);
            exit;
        }

        // Edit question
        if (post('action') == 'Edit') {
            try {
                $exam->data = array(
                    ':question_title'    =>  post('question_title', ''),
                    ':answer_option'     =>  post('answer_option', ''),
                    ':question_id'       =>  post('question_id', '')
                );

                $exam->query = "
                    UPDATE question_table
                    SET question_title = :question_title, answer_option = :answer_option
                    WHERE question_id = :question_id
                ";
                $exam->execute_query();

                for ($count = 1; $count <= 4; $count++) {
                    $exam->data = array(
                        ':question_id'  =>  post('question_id', ''),
                        ':option_number'=>  $count,
                        ':option_title' =>  post('option_title_' . $count, '')
                    );

                    $exam->query = "
                        UPDATE option_table
                        SET option_title = :option_title
                        WHERE question_id = :question_id
                        AND option_number = :option_number
                    ";
                    $exam->execute_query();
                }

                echo json_encode(['success' => 'Question Edit']);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Failed to edit question: ' . $e->getMessage()]);
            }
            exit;
        }

    } // end page == 'question'

    // ---------------------------
    // User page
    // ---------------------------
    if ($page === 'user') {

        if (post('action') == 'fetch') {
            $search = trim($_POST['search']['value'] ?? '');
            // Build base
            $exam->query = "SELECT * FROM user_table WHERE 1=1 ";

            if ($search !== '') {
                $sv = addslashes($search);
                $exam->query .= ' AND (user_email_address LIKE "%'.$sv.'%" OR user_name LIKE "%'.$sv.'%" OR user_gender LIKE "%'.$sv.'%" OR user_mobile_no LIKE "%'.$sv.'%") ';
            }

            if (isset($_POST['order'])) {
                $col = intval($_POST['order'][0]['column']);
                $dir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
                // Note: You may want a column map here; retained existing behavior
                $exam->query .= ' ORDER BY user_id '.$dir.' ';
            } else {
                $exam->query .= ' ORDER BY user_id DESC ';
            }

            $extra_query = '';
            if (isset($_POST['length']) && $_POST['length'] != -1) {
                $extra_query .= ' LIMIT ' . intval($_POST['start']) . ', ' . intval($_POST['length']);
            }

            $filterd_rows = $exam->total_row();
            $exam->query .= $extra_query;

            $result = $exam->query_result();

            $exam->query = "SELECT * FROM user_table";
            $total_rows = $exam->total_row();

            $data = [];
            foreach ($result as $row) {
                $sub_array = [];
                $sub_array[] = '<img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="75" />';
                $sub_array[] = $row["user_name"];
                $sub_array[] = $row["user_email_address"];
                $sub_array[] = $row["user_gender"];
                $sub_array[] = $row["user_mobile_no"];
                $is_email_verified = ($row["user_email_verified"] == 'yes') ? '<label class="badge badge-success">Yes</label>' : '<label class="badge badge-danger">No</label>';
                $sub_array[] = $is_email_verified;
                $sub_array[] = '';
                $data[] = $sub_array;
            }

            $output = array(
                "draw" => intval($_POST["draw"] ?? 0),
                "recordsTotal" => $total_rows,
                "recordsFiltered" => $filterd_rows,
                "data" => $data
            );

            echo json_encode($output);
            exit;
        }

        if (post('action') == 'fetch_data') {
            $user_id = post('user_id', '');
            $exam->query = "
                SELECT * FROM user_table
                WHERE user_id = '" . addslashes($user_id) . "'
            ";
            $result = $exam->query_result();
            $output = '';
            foreach ($result as $row) {
                $is_email_verified = ($row["user_email_verified"] == 'yes') ? '<label class="badge badge-success">Email Verified</label>' : '<label class="badge badge-danger">Email Not Verified</label>';
                $output .= '
                <div class="row">
                    <div class="col-md-12">
                        <div align="center">
                            <img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="200" />
                        </div>
                        <br />
                        <table class="table table-bordered">
                            <tr><th>Name</th><td>'.$row["user_name"].'</td></tr>
                            <tr><th>Gender</th><td>'.$row["user_gender"].'</td></tr>
                            <tr><th>Address</th><td>'.$row["user_address"].'</td></tr>
                            <tr><th>Mobile No.</th><td>'.$row["user_mobile_no"].'</td></tr>
                            <tr><th>Email</th><td>'.$row["user_email_address"].'</td></tr>
                            <tr><th>Email Status</th><td>'.$is_email_verified.'</td></tr>
                        </table>
                    </div>
                </div>
                ';
            }
            echo $output;
            exit;
        }
    }

    // ---------------------------
    // Exam Enroll
    // ---------------------------
    if ($page === 'exam_enroll') {
        if (post('action') == 'fetch') {
            $code = post('code', '');
            $exam_id = $exam->Get_exam_id($code);

            $exam->query = "
                SELECT * FROM user_exam_enroll_table 
                INNER JOIN user_table 
                ON user_table.user_id = user_exam_enroll_table.user_id  
                WHERE user_exam_enroll_table.exam_id = '". addslashes($exam_id) . "' 
            ";

            $search = trim($_POST['search']['value'] ?? '');
            if ($search !== '') {
                $sv = addslashes($search);
                $exam->query .= " AND (user_table.user_name LIKE '%{$sv}%' OR user_table.user_gender LIKE '%{$sv}%' OR user_table.user_mobile_no LIKE '%{$sv}%' OR user_table.user_email_verified LIKE '%{$sv}%') ";
            }

            if (isset($_POST['order'])) {
                $dir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
                $exam->query .= ' ORDER BY user_exam_enroll_table.user_exam_enroll_id ' . $dir . ' ';
            } else {
                $exam->query .= ' ORDER BY user_exam_enroll_table.user_exam_enroll_id ASC ';
            }

            $extra_query = '';
            if (isset($_POST['length']) && $_POST['length'] != -1) {
                $extra_query = ' LIMIT ' . intval($_POST['start']) . ', ' . intval($_POST['length']);
            }

            $filtered_rows = $exam->total_row();

            $exam->query .= $extra_query;

            $result = $exam->query_result();

            $exam->query = "
                SELECT * FROM user_exam_enroll_table 
                INNER JOIN user_table 
                ON user_table.user_id = user_exam_enroll_table.user_id  
                WHERE user_exam_enroll_table.exam_id = '". addslashes($exam_id) . "'
            ";
            $total_rows = $exam->total_row();

            $data = [];
            foreach ($result as $row) {
                $sub_array = [];
                $sub_array[] = "<img src='../upload/".$row["user_image"]."' class='img-thumbnail' width='75' />";
                $sub_array[] = $row["user_name"];
                $sub_array[] = $row["user_gender"];
                $sub_array[] = $row["user_mobile_no"];
                $is_email_verified = ($row['user_email_verified'] == 'yes') ? '<label class="badge badge-success">Yes</label>' : '<label class="badge badge-danger">No</label>';
                $sub_array[] = $is_email_verified;

                $result_btn = '';
                if ($exam->Get_exam_status($exam_id) == 'Completed') {
                    $result_btn = '<a href="user_exam_result.php?code='.$code.'&id='.$row['user_id'].'" class="btn btn-info btn-sm" target="_blank">Result</a>';
                }
                $sub_array[] = $result_btn;

                $data[] = $sub_array;
            }

            $output = array(
                "draw" => intval($_POST["draw"] ?? 0),
                "recordsTotal" => $total_rows,
                "recordsFiltered" => $filtered_rows,
                "data" => $data
            );

            echo json_encode($output);
            exit;
        }
    }

    // ---------------------------
    // Exam Result
    // ---------------------------
    if ($page === 'exam_result') {
        if (post('action') == 'fetch') {
            $code = post('code', '');
            $exam_id = $exam->Get_exam_id($code);

            $exam->query = "
                SELECT user_table.user_id, user_table.user_image, user_table.user_name, sum(user_exam_question_answer.marks) as total_mark
                FROM user_exam_question_answer
                INNER JOIN user_table ON user_table.user_id = user_exam_question_answer.user_id
                WHERE user_exam_question_answer.exam_id = '". addslashes($exam_id) . "'
            ";

            $search = trim($_POST['search']['value'] ?? '');
            if ($search !== '') {
                $sv = addslashes($search);
                $exam->query .= " AND (user_table.user_name LIKE '%{$sv}%') ";
            }

            $exam->query .= " GROUP BY user_exam_question_answer.user_id ";

            if (isset($_POST['order'])) {
                $dir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
                // ordering by column index may not match the selected column; kept original behavior
                $exam->query .= ' ORDER BY total_mark ' . $dir . ' ';
            } else {
                $exam->query .= ' ORDER BY total_mark DESC ';
            }

            $extra_query = '';
            if (isset($_POST['length']) && $_POST['length'] != -1) {
                $extra_query = ' LIMIT ' . intval($_POST['start']) . ', ' . intval($_POST['length']);
            }

            $filtered_rows = $exam->total_row();

            $exam->query .= $extra_query;

            $result = $exam->query_result();

            // compute total rows
            $exam->query = "
                SELECT user_table.user_image, user_table.user_name, sum(user_exam_question_answer.marks) as total_mark
                FROM user_exam_question_answer
                INNER JOIN user_table ON user_table.user_id = user_exam_question_answer.user_id
                WHERE user_exam_question_answer.exam_id = '". addslashes($exam_id) . "'
                GROUP BY user_exam_question_answer.user_id
                ORDER BY total_mark DESC
            ";
            $total_rows = $exam->total_row();

            $data = [];
            foreach ($result as $row) {
                $sub_array = [];
                $sub_array[] = '<img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="75" />';
                $sub_array[] = $row["user_name"];
                $sub_array[] = $exam->Get_user_exam_status($exam_id, $row["user_id"]);
                $sub_array[] = $row["total_mark"];
                $data[] = $sub_array;
            }

            $output = array(
                "draw" => intval($_POST["draw"] ?? 0),
                "recordsTotal" => $total_rows,
                "recordsFiltered" => $filtered_rows,
                "data" => $data
            );

            echo json_encode($output);
            exit;
        }
    }
} // end if isset page

// Default fallback
echo json_encode(['error' => 'Invalid request']);
exit;
?>
