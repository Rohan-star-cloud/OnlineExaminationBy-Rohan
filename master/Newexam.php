<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam Creation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section h2 {
            color: #4a5568;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s, box-shadow 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .question-block {
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background-color: #f8fafc;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .question-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .question-number {
            font-weight: bold;
            color: #667eea;
            font-size: 1.2rem;
        }
        
        .option-group {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        
        .option-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .option-input {
            flex: 1;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #48bb78;
        }
        
        .btn-success:hover {
            background: #3da768;
        }
        
        .btn-danger {
            background: #f56565;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Online Exam Creation System</h1>
            <p>Create and manage exams with multiple choice questions</p>
        </header>
        
        <div class="form-container">
            <div id="message" class="message"></div>
            
            <form id="examForm">
                <!-- Exam Basic Information -->
                <div class="form-section">
                    <h2>Exam Information</h2>
                    
                    <div class="form-group">
                        <label for="exam_title">Exam Title:</label>
                        <input type="text" id="exam_title" name="exam_title" required placeholder="Enter exam title">
                    </div>

                    <div class="form-group">
                        <label for="exam_datetime">Exam Date & Time:</label>
                        <input type="datetime-local" id="exam_datetime" name="exam_datetime" required>
                    </div>

                    <div class="form-group">
                        <label for="exam_duration">Duration (minutes):</label>
                        <input type="number" id="exam_duration" name="exam_duration" required min="1" placeholder="Enter exam duration in minutes">
                    </div>

                    <div class="form-group">
                        <label for="total_question">Total Questions:</label>
                        <input type="number" id="total_question" name="total_question" required min="1" value="1" readonly>
                    </div>

                    <div class="form-group">
                        <label for="marks_per_right_answer">Marks per Right Answer:</label>
                        <input type="text" id="marks_per_right_answer" name="marks_per_right_answer" required value="1">
                    </div>

                    <div class="form-group">
                        <label for="marks_per_wrong_answer">Marks per Wrong Answer:</label>
                        <input type="text" id="marks_per_wrong_answer" name="marks_per_wrong_answer" required value="0">
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="form-section">
                    <h2>Exam Questions</h2>
                    <div id="questions-container">
                        <div id="question-1" class="question-block">
                            <div class="question-header">
                                <div class="question-number">Question 1</div>
                                <button type="button" class="btn btn-danger" onclick="removeQuestion(1)" disabled>Remove</button>
                            </div>
                            
                            <div class="form-group">
                                <textarea name="questions[1][title]" required placeholder="Enter question text" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Correct Answer:</label>
                                <select name="questions[1][correct_answer]" required>
                                    <option value="1">Option 1</option>
                                    <option value="2">Option 2</option>
                                    <option value="3">Option 3</option>
                                    <option value="4">Option 4</option>
                                </select>
                            </div>

                            <div class="option-group">
                                <span class="option-number">1</span>
                                <input type="text" class="option-input" name="questions[1][options][1]" required placeholder="Enter option 1">
                            </div>
                            <div class="option-group">
                                <span class="option-number">2</span>
                                <input type="text" class="option-input" name="questions[1][options][2]" required placeholder="Enter option 2">
                            </div>
                            <div class="option-group">
                                <span class="option-number">3</span>
                                <input type="text" class="option-input" name="questions[1][options][3]" required placeholder="Enter option 3">
                            </div>
                            <div class="option-group">
                                <span class="option-number">4</span>
                                <input type="text" class="option-input" name="questions[1][options][4]" required placeholder="Enter option 4">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn" onclick="addQuestion()">Add Another Question</button>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-danger" onclick="resetForm()">Reset Form</button>
                    <button type="submit" class="btn btn-success">Create Exam</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let questionCount = 1;

        // Add a new question to the form
        function addQuestion() {
            questionCount++;
            const questionsContainer = document.getElementById('questions-container');
            
            const newQuestion = document.createElement('div');
            newQuestion.id = `question-${questionCount}`;
            newQuestion.className = 'question-block';
            
            newQuestion.innerHTML = `
                <div class="question-header">
                    <div class="question-number">Question ${questionCount}</div>
                    <button type="button" class="btn btn-danger" onclick="removeQuestion(${questionCount})">Remove</button>
                </div>
                
                <div class="form-group">
                    <textarea name="questions[${questionCount}][title]" required placeholder="Enter question text" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Correct Answer:</label>
                    <select name="questions[${questionCount}][correct_answer]" required>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                    </select>
                </div>

                <div class="option-group">
                    <span class="option-number">1</span>
                    <input type="text" class="option-input" name="questions[${questionCount}][options][1]" required placeholder="Enter option 1">
                </div>
                <div class="option-group">
                    <span class="option-number">2</span>
                    <input type="text" class="option-input" name="questions[${questionCount}][options][2]" required placeholder="Enter option 2">
                </div>
                <div class="option-group">
                    <span class="option-number">3</span>
                    <input type="text" class="option-input" name="questions[${questionCount}][options][3]" required placeholder="Enter option 3">
                </div>
                <div class="option-group">
                    <span class="option-number">4</span>
                    <input type="text" class="option-input" name="questions[${questionCount}][options][4]" required placeholder="Enter option 4">
                </div>
            `;
            
            questionsContainer.appendChild(newQuestion);
            
            // Update total questions count
            document.getElementById('total_question').value = questionCount;
            
            // Enable remove button for the first question if there are multiple questions
            if (questionCount > 1) {
                document.querySelector('#question-1 .btn-danger').disabled = false;
            }
        }

        // Remove a question from the form
        function removeQuestion(questionId) {
            if (questionCount > 1) {
                const questionElement = document.getElementById(`question-${questionId}`);
                questionElement.remove();
                questionCount--;
                document.getElementById('total_question').value = questionCount;
                
                // Re-number remaining questions
                const questions = document.querySelectorAll('.question-block');
                questions.forEach((question, index) => {
                    const newNumber = index + 1;
                    question.id = `question-${newNumber}`;
                    const questionNumberElement = question.querySelector('.question-number');
                    questionNumberElement.textContent = `Question ${newNumber}`;
                    
                    // Update all input names
                    const textarea = question.querySelector('textarea');
                    textarea.name = `questions[${newNumber}][title]`;
                    
                    const select = question.querySelector('select');
                    select.name = `questions[${newNumber}][correct_answer]`;
                    
                    const options = question.querySelectorAll('.option-input');
                    options.forEach((option, optIndex) => {
                        option.name = `questions[${newNumber}][options][${optIndex + 1}]`;
                    });
                    
                    // Update remove button onclick
                    const removeBtn = question.querySelector('.btn-danger');
                    removeBtn.setAttribute('onclick', `removeQuestion(${newNumber})`);
                });
                
                // Disable remove button for the first question if there's only one
                if (questionCount === 1) {
                    document.querySelector('#question-1 .btn-danger').disabled = true;
                }
            }
        }

        // Reset the entire form
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All data will be lost.')) {
                document.getElementById('examForm').reset();
                
                // Remove all questions except the first one
                const questionsContainer = document.getElementById('questions-container');
                while (questionsContainer.children.length > 1) {
                    questionsContainer.removeChild(questionsContainer.lastChild);
                }
                
                // Reset question count
                questionCount = 1;
                document.getElementById('total_question').value = 1;
                
                // Disable remove button for the first question
                document.querySelector('#question-1 .btn-danger').disabled = true;
                
                showMessage('Form has been reset.', 'success');
            }
        }

        // Show message to user
        function showMessage(message, type) {
            const messageElement = document.getElementById('message');
            messageElement.textContent = message;
            messageElement.className = `message ${type}`;
            messageElement.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }

        // Handle form submission
        document.getElementById('examForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const examTitle = document.getElementById('exam_title').value;
            const examDatetime = document.getElementById('exam_datetime').value;
            
            if (!examTitle || !examDatetime) {
                showMessage('Please fill in all required fields.', 'error');
                return;
            }
            
            // Validate that all questions have text and options
            let valid = true;
            const questions = document.querySelectorAll('.question-block');
            
            questions.forEach((question, index) => {
                const questionText = question.querySelector('textarea').value;
                const options = question.querySelectorAll('.option-input');
                
                if (!questionText.trim()) {
                    showMessage(`Question ${index + 1} is missing text.`, 'error');
                    valid = false;
                    return;
                }
                
                options.forEach(option => {
                    if (!option.value.trim()) {
                        showMessage(`Question ${index + 1} has empty options.`, 'error');
                        valid = false;
                        return;
                    }
                });
            });
            
            if (!valid) return;
            
            // In a real application, you would send the data to a server here
            // For this demo, we'll just show a success message
            
            // Simulate API call
            showMessage('Creating exam...', 'success');
            
            setTimeout(() => {
                // Generate a random exam code
                const examCode = Math.random().toString(36).substring(2, 10).toUpperCase();
                
                showMessage(`Exam created successfully! Exam Code: ${examCode}`, 'success');
                
                // You would typically redirect or reset the form here
                // resetForm();
            }, 1500);
        });

        // Set minimum datetime to current time
        document.getElementById('exam_datetime').min = new Date().toISOString().slice(0, 16);
    </script>
</body>
</html>