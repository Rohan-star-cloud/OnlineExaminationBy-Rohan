<?php 
// exam.php
include('header.php');
?>

<br />

<div class="card">
    <div class="card-header">
        <h3>Exam List</h3>
        <button type="button" class="btn btn-success btn-sm" id="add_button">Add Exam</button>
    </div>

    <div class="card-body">
        <span id="message_operation"></span>

        <div class="table-responsive">
            <table id="exam_data_table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Exam Title</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Total Question</th>
                        <th>Right Answer Mark</th>
                        <th>Wrong Answer Mark</th>
                        <th>Status</th>
                        <th>Enroll</th>
                        <th>Question</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<!-- ============================
   ADD / EDIT EXAM MODAL
============================= -->
<div class="modal" id="formModal">
    <div class="modal-dialog modal-lg">
        <form method="post" id="exam_form">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title" id="modal_title">Add Exam</h4>
                    <button class="close" type="button" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Exam Title*</label>
                        <input type="text" name="online_exam_title" id="online_exam_title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Exam Date & Time*</label>
                        <input type="text" name="online_exam_datetime" id="online_exam_datetime" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label>Exam Duration*</label>
                        <select name="online_exam_duration" id="online_exam_duration" class="form-control">
                            <option value="">Select</option>
                            <option value="5">5 Minute</option>
                            <option value="30">30 Minute</option>
                            <option value="60">1 Hour</option>
                            <option value="120">2 Hour</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Total Questions*</label>
                        <select name="total_question" id="total_question" class="form-control">
                            <option value="">Select</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Marks per Right Answer*</label>
                        <select name="marks_per_right_answer" id="marks_per_right_answer" class="form-control">
                            <option value="">Select</option>
                            <option value="1">+1</option>
                            <option value="2">+2</option>
                            <option value="3">+3</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Marks per Wrong Answer*</label>
                        <select name="marks_per_wrong_answer" id="marks_per_wrong_answer" class="form-control">
                            <option value="">Select</option>
                            <option value="1">-1</option>
                            <option value="2">-2</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <input type="hidden" name="online_exam_id" id="online_exam_id">
                    <input type="hidden" name="page" value="exam">
                    <input type="hidden" name="action" id="action" value="Add">
                    <input type="submit" id="button_action" class="btn btn-success btn-sm" value="Add">
                    <button class="btn btn-danger btn-sm" type="button" data-dismiss="modal">Close</button>
                </div>

            </div>
        </form>
    </div>
</div>


<!-- ============================
       DELETE CONFIRM MODAL
============================= -->
<div class="modal" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4>Delete Confirmation</h4>
                <button class="close" type="button" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <h4 class="text-center">Are you sure?</h4>
            </div>

            <div class="modal-footer">
                <button id="ok_button" class="btn btn-primary btn-sm">OK</button>
                <button class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>


<!-- ============================
       QUESTION MODAL
============================= -->
<div class="modal" id="questionModal">
    <div class="modal-dialog modal-lg">
        <form method="post" id="question_form">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 id="question_modal_title">Add Question</h4>
                    <button class="close" type="button" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Question Title*</label>
                        <input type="text" name="question_title" id="question_title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Option 1*</label>
                        <input type="text" name="option_title_1" id="option_title_1" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Option 2*</label>
                        <input type="text" name="option_title_2" id="option_title_2" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Option 3*</label>
                        <input type="text" name="option_title_3" id="option_title_3" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Option 4*</label>
                        <input type="text" name="option_title_4" id="option_title_4" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Correct Answer*</label>
                        <select name="answer_option" id="answer_option" class="form-control">
                            <option value="">Select</option>
                            <option value="1">1 Option</option>
                            <option value="2">2 Option</option>
                            <option value="3">3 Option</option>
                            <option value="4">4 Option</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <input type="hidden" name="question_id" id="question_id">
                    <input type="hidden" name="online_exam_id" id="hidden_online_exam_id">
                    <input type="hidden" name="page" value="question">
                    <input type="hidden" name="action" id="hidden_action" value="Add">
                    <input type="submit" id="question_button_action" class="btn btn-success btn-sm" value="Add">
                    <button class="btn btn-danger btn-sm" data-dismiss="modal">Close</button>
                </div>

            </div>
        </form>
    </div>
</div>




<script>
$(document).ready(function(){

    var dataTable = $('#exam_data_table').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        ajax: {
            url: "ajax_action.php",
            type: "POST",
            data: { action: "fetch", page: "exam" }
        },
        columnDefs: [
            { orderable: false, targets: [7,8,9,10] }
        ]
    });

    function reset_form() {
        $('#modal_title').text('Add Exam');
        $('#action').val('Add');
        $('#button_action').val('Add');
        $('#exam_form')[0].reset();
    }

    $('#add_button').click(function(){
        reset_form();
        $('#formModal').modal('show');
    });

    $('#exam_form').on('submit', function(e){
        e.preventDefault();

        $.ajax({
            url: "ajax_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            beforeSend: function(){
                $('#button_action').val('Validating...').prop('disabled', true);
            },
            success: function(data){
                $('#button_action').prop('disabled', false).val($('#action').val());

                if(data.success){
                    $('#message_operation').html('<div class="alert alert-success">'+data.success+'</div>');
                    $('#formModal').modal('hide');
                    dataTable.ajax.reload();
                } else if(data.error){
                    $('#message_operation').html('<div class="alert alert-danger">'+data.error+'</div>');
                }
            },
            error: function(xhr){
                $('#message_operation').html('<div class="alert alert-danger">Server error: '+xhr.responseText+'</div>');
                $('#button_action').prop('disabled', false).val($('#action').val());
            }
        });
    });

    let exam_id = "";

    $(document).on('click', '.edit', function(){
        exam_id = $(this).attr('id');
        $.ajax({
            url: "ajax_action.php",
            type: "POST",
            data: { exam_id: exam_id, action: 'edit_fetch', page: 'exam' },
            dataType: "json",
            success: function(data){
                $('#online_exam_title').val(data.online_exam_title);
                $('#online_exam_datetime').val(data.online_exam_datetime);
                $('#online_exam_duration').val(data.online_exam_duration);
                $('#total_question').val(data.total_question);
                $('#marks_per_right_answer').val(data.marks_per_right_answer);
                $('#marks_per_wrong_answer').val(data.marks_per_wrong_answer);

                $('#modal_title').text("Edit Exam");
                $('#action').val("Edit");
                $('#button_action').val("Edit");
                $('#online_exam_id').val(exam_id);
                $('#formModal').modal('show');
            }
        });
    });

    $(document).on('click', '.delete', function(){
        exam_id = $(this).attr('id');
        $('#deleteModal').modal('show');
    });

    $('#ok_button').click(function(){
        $.ajax({
            url: "ajax_action.php",
            type: "POST",
            data: { exam_id: exam_id, action:'delete', page:'exam' },
            dataType: "json",
            success: function(data){
                $('#message_operation').html('<div class="alert alert-success">'+data.success+'</div>');
                $('#deleteModal').modal('hide');
                dataTable.ajax.reload();
            }
        });
    });

    function reset_question_form(){
        $('#question_form')[0].reset();
        $('#hidden_action').val("Add");
        $('#question_button_action').val("Add");
    }

    $(document).on('click', '.add_question', function(){
        reset_question_form();
        exam_id = $(this).attr("id");
        $('#hidden_online_exam_id').val(exam_id);
        $('#questionModal').modal('show');
    });

    $('#question_form').on('submit', function(e){
        e.preventDefault();

        $.ajax({
            url: "ajax_action.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            beforeSend: function(){
                $('#question_button_action').val("Validating...").prop('disabled', true);
            },
            success: function(data){
                $('#question_button_action').prop('disabled', false).val($('#hidden_action').val());

                if(data.success){
                    $('#message_operation').html('<div class="alert alert-success">'+data.success+'</div>');
                    $('#questionModal').modal('hide');
                    dataTable.ajax.reload();
                } else if(data.error){
                    $('#message_operation').html('<div class="alert alert-danger">'+data.error+'</div>');
                }
            },
            error: function(xhr){
                $('#message_operation').html('<div class="alert alert-danger">Server error: '+xhr.responseText+'</div>');
                $('#question_button_action').prop('disabled', false).val($('#hidden_action').val());
            }
        });
    });

});
</script>


<?php 
include('footer.php');
?>
