
<?php
//index.php
include('master/Examination.php');
$exam = new Examination;
include('header.php');

// Get user name if logged in
$user_name = '';
if(isset($_SESSION["user_id"])) {
    $exam->query = "SELECT user_name FROM user_table WHERE user_id = ".$_SESSION["user_id"];
    $result = $exam->query_result();
    if(!empty($result)) {
        $user_name = $result[0]['user_name'];
    }
}
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #0061f2 0%, #00c6f2 100%);
    padding: 4rem 0;
    margin-bottom: 3rem;
    color: white;
    position: relative;
    overflow: hidden;
}
.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: url('https://cdn-icons-png.flaticon.com/512/3976/3976631.png') no-repeat center right;
    background-size: 300px;
    opacity: 0.15;
}
.exam-card {
    border-radius: 15px;
    border: none;
    transition: all 0.3s ease;
    overflow: hidden;
}
.exam-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.feature-icon {
    width: 60px;
    height: 60px;
    background: rgba(0,97,242,0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: #0061f2;
    font-size: 24px;
}
.custom-select {
    padding: 1rem;
    border-radius: 10px;
    border: 2px solid #e9ecef;
    font-size: 1.1rem;
    background-color: white;
    transition: all 0.3s ease;
}
.custom-select:focus {
    border-color: #0061f2;
    box-shadow: 0 0 0 0.2rem rgba(0,97,242,0.25);
}
.logo-section {
    padding: 2rem;
    background: rgba(0,97,242,0.03);
    border-radius: 15px;
}
</style>

<?php if(isset($_SESSION["user_id"])) { ?>
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Welcome<?php echo !empty($user_name) ? ', '.$user_name : ''; ?>!</h1>
                <p class="lead mb-0">Get ready to showcase your knowledge. Choose an exam below to begin your assessment.</p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Features Section -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 exam-card">
                <div class="card-body text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h5>Secure Testing</h5>
                    <p class="text-muted mb-0">Take exams in a secure and monitored environment</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 exam-card">
                <div class="card-body text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Instant Results</h5>
                    <p class="text-muted mb-0">Get your results and analysis immediately</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 exam-card">
                <div class="card-body text-center">
                    <div class="feature-icon mx-auto">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h5>Track Progress</h5>
                    <p class="text-muted mb-0">Monitor your performance and improvements</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Selection Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card exam-card">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Available Exams</h3>
                    <div class="exam-select-wrapper position-relative">
                        <select name="exam_list" id="exam_list" class="form-control custom-select">
                            <option value="">Choose your exam</option>
                            <?php echo $exam->Fill_exam_list(); ?>
                        </select>
                        <div class="exam-select-icon">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Small Branding -->
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <p class="text-muted small">© <?php echo date('Y'); ?> Online Examination System</p>
        </div>
    </div>
</div>

<style>
.exam-select-wrapper {
    position: relative;
}
.exam-select-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #0061f2;
    pointer-events: none;
}
.custom-select {
    appearance: none;
    -webkit-appearance: none;
    padding: 1.2rem;
    font-size: 1.1rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background-color: white;
    transition: all 0.3s ease;
    cursor: pointer;
}
.custom-select:hover {
    border-color: #0061f2;
    box-shadow: 0 0 0 4px rgba(0,97,242,0.1);
}
.custom-select:focus {
    border-color: #0061f2;
    box-shadow: 0 0 0 4px rgba(0,97,242,0.1);
    outline: none;
}
</style>

<script>
$(document).ready(function() {
    $('#exam_list').change(function() {
        var examId = $(this).val();
        if(examId) {
            window.location.href = 'enroll_exam.php';
        }
    });
});
</script>
<?php } ?>
	<!-- Add Modal for Exam Details -->
	<div class="modal fade" id="examDetailsModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Exam Details</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body" id="examDetailsContent">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="takeExamBtn" style="display:none">Take Exam</button>
				</div>
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function(){
		$('#exam_list').parsley();
		var exam_id = '';
		
		$('#exam_list').change(function(){
			$('#exam_list').attr('required', 'required');
			if($('#exam_list').parsley().validate()) {
				exam_id = $('#exam_list').val();
				
				// Fetch exam details
				$.ajax({
					url: "user_ajax_action.php",
					method: "POST",
					data: {action:'fetch_exam', page:'index', exam_id:exam_id},
					success: function(data) {
						try {
							const examData = JSON.parse(data);
							// Get exact exam date and time
							const examDate = new Date(examData.online_exam_datetime);
							const now = new Date();
							
							// Format dates for comparison
							const examDateStr = examDate.toISOString().split('T')[0];
							const todayStr = now.toISOString().split('T')[0];

							// Prepare modal content
							let modalContent = `
								<div class="alert ${today.getTime() === examDate.getTime() ? 'alert-success' : 'alert-warning'} mb-3">
									<strong>Exam Date:</strong> ${examData.online_exam_datetime}
								</div>
								<p><strong>Title:</strong> ${examData.online_exam_title}</p>
								<p><strong>Duration:</strong> ${examData.online_exam_duration} Minutes</p>
								<p><strong>Total Questions:</strong> ${examData.total_question}</p>
								<p><strong>Marks per Right Answer:</strong> +${examData.marks_per_right_answer}</p>
								<p><strong>Marks per Wrong Answer:</strong> ${examData.marks_per_wrong_answer}</p>
							`;

							if (examDateStr === todayStr) {
								modalContent += '<div class="alert alert-success">This exam is available today!</div>';
								$('#takeExamBtn').show();
							} else {
								if (examDate < now) {
									modalContent += '<div class="alert alert-danger">This exam has already passed.</div>';
								} else {
									modalContent += `<div class="alert alert-warning">
										This exam is scheduled for ${examData.online_exam_datetime}.<br>
										You can only take this exam on the scheduled date.
									</div>`;
								}
								$('#takeExamBtn').hide();
							}

							$('#examDetailsContent').html(modalContent);
							$('#examDetailsModal').modal('show');
						} catch (e) {
							console.error('Error parsing exam data:', e);
						}
					}
				});
			}
		});

		// Handle Take Exam button click
		$('#takeExamBtn').click(function() {
			window.location.href = 'enroll_exam.php?exam_id=' + exam_id;
		});
	});<script>
    // Your JS code here
</script>

<?php if(isset($_SESSION["user_id"])) { ?>
    <!-- HTML for logged in users -->
    <div class="hero-section">
        ...
    </div>
<?php } else { ?>
    <!-- HTML for guests -->
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <a href="http://localhost/finalproject/old/rohan/register.php" class="btn btn-warning btn-lg me-2">Register</a>
            <a href="http://localhost/new%20by%20sam/login.php" class="btn btn-dark btn-lg">Login</a>
        </div>
    </div>
<?php } ?>

</body>
</html>
