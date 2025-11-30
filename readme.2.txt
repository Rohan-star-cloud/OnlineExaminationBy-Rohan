Sure! Here's the updated **README.md** content including the **Short Quiz** feature with all relevant additions clearly integrated into the original structure:

---

# Online Examination System

A web-based examination platform built with PHP, MySQL, and jQuery that allows administrators to create and manage exams, and users to take exams and view results.

---

## Program Flow Overview

### User Side (Student)

#### 1. **User Registration & Login**

* **File**: `register.php`, `login.php`
* **Database Tables**: `user_table`
* **Flow**:

  * User fills registration form (name, email, password, image upload)
  * Form submits to `user_ajax_action.php` (action: `user_register`)
  * Server validates and hashes password, stores user data
  * User logs in via `login.php`
  * Login AJAX posts credentials to `user_ajax_action.php` (action: `user_login`)
  * On success, session is created and user redirected to dashboard

#### 2. **Exam Enrollment**

* **Files**: `enroll_exam.php`, `user_ajax_action.php`
* **Database Tables**: `online_exam_table`, `user_exam_enroll_table`, `user_exam_question_answer`
* **Flow**:

  * User views available exams via `enroll_exam.php`
  * Clicks "Enroll" button → AJAX calls `user_ajax_action.php` (action: `fetch_exam`)
  * Server checks if user already enrolled; if not, creates entry in `user_exam_enroll_table`
  * Button changes to "Take Exam"
  * User clicks "Take Exam" → directed to `take_exam.php` with `exam_id` parameter

#### 3. **Exam Attempt (Take Exam)**

* **Files**: `take_exam.php`, `exam_start.php`, `exam.js`, `submit_exam.php`
* **Database Tables**: `user_exam_question_answer`, `question_table`, `online_exam_table`
* **Flow**:

  * User taken to `take_exam.php` → displays exam instructions and countdown timer
  * Timer implemented in `exam.js` with alerts at 5 min warning and auto-submit at 0:00
  * User answers questions via `exam_start.php` interface
  * Each question rendered from `question_table` with multiple choice options
  * User can navigate between questions (shuffled order via JavaScript)
  * On time expiry or manual submit, answers posted to `submit_exam.php`
  * Server records answers in `user_exam_question_answer` table (with marks calculation)
  * Marks assigned based on `marks_per_right_answer` from `online_exam_table`

#### 4. **View Results**

* **Files**: `result.php`, `user_exam_result` (table)
* **Database Tables**: `user_exam_result`, `user_table`
* **Flow**:

  * User navigates to `result.php` to view past exam results
  * Displays total marks, percentage, and detailed question-wise feedback
  * Data fetched from `user_exam_result` table (pre-aggregated by system)

#### 5. **Profile Management**

* **Files**: `profile.php`, `user_ajax_action.php`
* **Database Tables**: `user_table`
* **Flow**:

  * User visits `profile.php` to view/edit profile
  * Updates name, email, or profile picture via form
  * Form posts to `user_ajax_action.php` (action: `user_profile_update`)
  * Server validates and updates `user_table`
  * Session updated with new user data

#### 6. **Short Quiz Participation**

* **Files**: `short_quiz_list.php`, `short_quiz_take.php`, `short_quiz_submit.php`, `short_quiz_result.php`
* **Database Tables**: `short_quiz`, `short_quiz_question`, `short_quiz_option`, `user_short_quiz_attempt`, `user_short_quiz_answer`
* **Flow**:

  * User visits `short_quiz_list.php` to see available short quizzes
  * Clicks “Take Quiz” to start `short_quiz_take.php` with quiz questions and timer
  * User answers questions, submits manually or on timer expiry
  * Answers posted to `short_quiz_submit.php`, score calculated and stored
  * User views results and feedback on `short_quiz_result.php`

---

### Admin Side

#### 1. **Admin Login**

* **File**: `master/login.php`, `master/ajax_action.php`
* **Database Tables**: `admin_table`
* **Flow**:

  * Admin enters credentials in `master/login.php`
  * AJAX posts to `master/ajax_action.php` (action: `admin_login`)
  * Server validates against `admin_table` and creates admin session
  * Redirected to admin dashboard (`master/index.php`)

#### 2. **Exam Management**

* **Files**: `master/exam.php`, `master/ajax_action.php`, `master/Examination.php`
* **Database Tables**: `online_exam_table`
* **Flow**:

  * Admin views list of all exams in `master/exam.php`
  * AJAX calls `master/ajax_action.php` (action: `fetch_exam_list`, `add_exam`, `update_exam`, `delete_exam`)
  * New exam creation:

    * Admin enters exam title, duration, marks per right/wrong answer
    * Server validates and inserts into `online_exam_table`
  * Exam editing updates exam metadata
  * Exam deletion removes exam and associated data

#### 3. **Question Management**

* **Files**: `master/question.php`, `master/ajax_action.php`
* **Database Tables**: `question_table`, `question_option_table`, `online_exam_table`
* **Flow**:

  * Admin navigates to question editor for selected exam
  * Adds/edits/deletes questions with multiple choice options
  * AJAX posts to `master/ajax_action.php` (action: `add_question`, `update_question`, `delete_question`)
  * Each question linked to exam and stored with correct answer marked

#### 4. **View Aggregated Results (Grade Computation)**

* **Files**: `master/exam_result_agg.php`, `master/Examination.php`
* **Database Tables**: `user_exam_result`, `user_exam_question_answer`, `user_table`, `user_exam_enroll_table`, `question_table`, `online_exam_table`
* **Algorithm**: **Borda Count Rank Aggregation** (implemented in PHP, not SQL)
* **Flow**:

  * Admin visits `master/exam_result_agg.php`
  * Page auto-loads first exam or accepts `?exam_id=X` parameter
  * Grade Computation Algorithm computes rank combining attendance and performance
  * Display results in HTML table with user info, marks, percentage, and Borda-based ranking

#### 5. **User Enrollment Management**

* **Files**: `master/exam_enroll.php`, `master/ajax_action.php`
* **Database Tables**: `user_exam_enroll_table`
* **Flow**:

  * Admin views enrolled users for each exam
  * Can mark attendance or remove enrollments via AJAX

#### 6. **Generate Reports**

* **Files**: `master/pdf_exam_result.php`, `master/exam_result.php`
* **Database Tables**: `user_exam_result`
* **Flow**:

  * Admin can export individual user results as PDF
  * Uses `dompdf` library for PDF generation

#### 7. **Short Quiz Management (New Feature)**

* **Files**:

  * `master/short_quiz.php` (list/manage quizzes)
  * `master/short_quiz_add_edit.php` (add/edit quiz metadata)
  * `master/short_quiz_question.php` (manage quiz questions and options)
  * `master/short_quiz_ajax_action.php` (AJAX backend for CRUD)
* **Database Tables**: `short_quiz`, `short_quiz_question`, `short_quiz_option`
* **Flow**:

  * Admin can create, edit, and delete short quizzes
  * Manage quiz questions with multiple-choice options
  * Changes saved via AJAX calls

---

## Grade Computation Algorithm (Borda Count)

### Algorithm Overview

Located in: **`master/exam_result_agg.php`** (lines 96-160)

**Purpose**: Rank users by combining two independent metrics (exam performance + attendance) in a fair, balanced way.

...

*(Algorithm details unchanged)*

---

## Database Schema Summary (Including Short Quiz)

| Table                       | Purpose                                   |
| --------------------------- | ----------------------------------------- |
| `user_table`                | Stores user registration data             |
| `admin_table`               | Stores admin credentials                  |
| `online_exam_table`         | Exam metadata                             |
| `question_table`            | Questions for each exam                   |
| `question_option_table`     | Multiple choice options                   |
| `user_exam_enroll_table`    | Records user enrollment and attendance    |
| `user_exam_question_answer` | User answers with marks for each question |
| `user_exam_result`          | Aggregated results per user per exam      |
| `short_quiz`                | Metadata for short quizzes                |
| `short_quiz_question`       | Questions for short quizzes               |
| `short_quiz_option`         | MCQ options for short quiz questions      |
| `user_short_quiz_attempt`   | Records user attempts on short quizzes    |
| `user_short_quiz_answer`    | User answers for each short quiz attempt  |

---

## Key Technologies

* **Backend**: PHP 7.x with PDO (prepared statements for security)
* **Database**: MySQL / MariaDB
* **Frontend**: jQuery, Bootstrap, Parsley.js for validation
* **PDF Export**: dompdf library
* **Session Management**: PHP built-in sessions

---

## API Endpoints (AJAX Actions)

### User Actions (`user_ajax_action.php`)

| Action                         | Description                              |
| ------------------------------ | ---------------------------------------- |
| user_register                  | Register new user                        |
| user_login                     | User login                               |
| user_profile_update            | Update user profile                      |
| change_password                | Change user password                     |
| fetch_exam                     | Fetch exam details and handle enrollment |
| check_schedule                 | Check if user can take exam              |
| **fetch_short_quiz_list**      | Fetch list of short quizzes              |
| **fetch_short_quiz_questions** | Fetch questions of a short quiz          |
| **submit_short_quiz_attempt**  | Submit answers for a short quiz          |

### Admin Actions (`master/ajax_action.php` & `master/short_quiz_ajax_action.php`)

| Action                         | Description                 |
| ------------------------------ | --------------------------- |
| admin_login                    | Admin login                 |
| fetch_exam_list                | Get all exams (paginated)   |
| add_exam                       | Create new exam             |
| update_exam                    | Update exam metadata        |
| delete_exam                    | Delete exam                 |
| add_question                   | Add question to exam        |
| update_question                | Update question and options |
| delete_question                | Delete question             |
| **fetch_short_quiz_list**      | Fetch all short quizzes     |
| **add_short_quiz**             | Create short quiz           |
| **update_short_quiz**          | Update short quiz metadata  |
| **delete_short_quiz**          | Delete short quiz           |
| **add_short_quiz_question**    | Add question to short quiz  |
| **update_short_quiz_question** | Update short quiz question  |
| **delete_short_quiz_question** | Delete short quiz question  |

---

## File Structure (Updated)

```
new by sam/
├── register.php
├── login.php
├── enroll_exam.php
├── take_exam.php
├── exam_start.php
├── submit_exam.php
├── result.php
├── profile.php
├── change_password.php
├── exam.js
├── short_quiz_list.php         # User: List available short quizzes
├── short_quiz_take.php         # User: Take a short quiz
├── short_quiz_submit.php       # User: Submit short quiz answers
├── short_quiz_result.php       # User: View short quiz result
├── user_ajax_action.php        # User AJAX (includes short quiz)
├── master/
│   ├── index.php
│   ├── login.php
│   ├── exam.php
│   ├── question.php
│   ├── exam_enroll.php
│   ├── exam_result_agg.php
│   ├── exam_result.php
│   ├── pdf_exam_result.php
│   ├── ajax_action.php
│   ├── short_quiz.php          # Admin: Manage short quizzes
│   ├── short_quiz_add_edit.php# Admin: Add/Edit short quiz metadata
│   ├── short_quiz_question.php# Admin: Manage short quiz questions/options
│   ├── short_quiz_ajax_action.php # Admin AJAX for short quiz CRUD
│   ├── Examination.php
│   ├── header.php
│   └── footer.php
├── class/
│   ├── class.phpmailer.php
│   ├── pdf.php
│   └── dompdf/
└── online_examination.sql
```

---

## Installation & Setup

*Same as before, with new database tables for short quizzes added.*

---

## Testing

* User:

  * Register → Login → Enroll in Exam → Take Exam → View Results
  * **Take Short Quiz → View Short Quiz Results**

* Admin:

  * Login → Create Exam → Add Questions → View Aggregated Results
  * **Create Short Quiz → Add/Edit Questions → Manage Short Quiz**

---

## Future Enhancements

* Role-based access control (teacher, student, admin)
* Negative marking for wrong answers
* Time-weighted scoring (faster completion = bonus points)
* Detailed analytics and performance charts
* Email notifications for exam and quiz schedules
* Mobile app integration via REST API

---

If you want, I can also provide the SQL for new tables or sample code files for this new Short Quiz feature.

---

**Would you like me to generate those?**
