<?php @ob_start(); if (!isset($_SESSION)) {session_start();} ?>
<style>
    textarea {
        resize: none;
        max-height: 150px;
        border: 1px solid #999;
        outline: none;
        font: 18px sans-serif;
        color: #333;
        width: 100%;
        padding: 8px 14px;
        box-sizing: border-box;
    }
    
    #myModalViewFile .modal-dialog{
            height:93%;
            min-width:100%;
            /* top:-10px; */
            left: 0px;
    }
    
    #myModalViewFile .modal-content{
        height:100%;
        min-width:90%;
    }
    
    #myModalViewFile .modal-body{
        height:100%;
        min-width:100%;
        overflow:auto
    }

    /* div {
        border:1px solid red;
    } */
</style>


<div class="row">
    <div class="col-12">
        <div class="card">
            <div id="modal-user" class="modal fade" data-keyboard="true" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header bg-primary">
                            <h4 class="modal-title" id="modalHeaderUser"></h4>
                            <button type="button" class="close" data-dismiss="modal"  aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <label id="lblrequestdate">Request Date</label>
                                <input type="Date" class="form-control" id="itrequestdate" value="<?php date_default_timezone_set("asia/manila"); echo date('Y-m-d'); ?>" disabled>
                                <div>
                                        <?php 
                                        // Include config file
                                        require_once "dbconnect.php";
                                        // Attempt select query execution
                                        $sql = "SELECT userfiletbl.deptid,departmentfiletbl.deptname FROM userfiletbl
                                                Left Outer Join departmentfiletbl on userfiletbl.deptid = departmentfiletbl.deptid
                                                Where userid = '".$_SESSION['userid']."'";
                                        if($result = mysqli_query($conn, $sql)){
                                            if(mysqli_num_rows($result) > 0){
                                                $row = mysqli_fetch_array($result);
                                                echo '<label>Request from Department</label>
                                                <input type="text" class="form-control" id="itdepartmentfrom" name="'.$row['deptid'].'" value="'.$row['deptname'].'" disabled>';
                                            }
                                        }
                                        ?>  
                                </div>
                                <div>
                                    <label>Approve/Verify to this Department</label>
                                    <span class="requiredfield" id="departmentUserErr"><b></b></span>
                                    <select name="department" id="selectdepartment" class="form-control">
                                        <option value="" selected disabled hidden>Select Here...</option>
                                        <?php 
                                        // Include config file
                                        require_once "dbconnect.php";
                                        // Attempt select query execution
                                        $sql = "SELECT * FROM departmentfiletbl order by deptname Asc";
                                        if($result = mysqli_query($conn, $sql)){
                                            
                                            if(mysqli_num_rows($result) > 0){
                                            $x = 0;
                                            while($row = mysqli_fetch_array($result)){
                                                ++$x;
                                                echo '<option value="'.$row['deptid'].'">'.$row['deptname'].'</option>';
                                            }
                                            }
                                        }
                                        ?>  
                                    </select>
                                </div>
                                
                                <div>
                                    <label id="lblremarks">Remarks</label>
                                </div>
                                <div class="form-group">
                                    <textarea maxlength="500" rows="4" class="h5" id="taremarks" name="comment" form="usrform"></textarea>
                                </div>
                            
                                <br />  
                                <label>Select PDF File to upload:</label>
                                <div>
                                    <div class="row p-3">
                                        <div class="col-sm" ><input type="file" name="itview_upload_file" id="itview_upload_file" accept="application/pdf,application/vnd.ms-excel" /></div>
                                        <div class="col-sm offset-sm-2 bg-light align-self-end">
                                            <!-- <a data-toggle="modal" href="#modal_view_upload_file"> -->
                                                <button type="button" onclick="view_upload_filefn()" name="view_upload_file" id="btnview_upload_file" style="width: 100px; border-radius: 5px;">View File</button>
                                            <!-- </a> -->
                                        </div>   
                                            
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" onclick="window.location.reload();" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
                            <button type="button" onclick="saverequest()" class="btn btn-success" id="save-btn">Save</button>
                            <!-- <a href="#myModalViewFile" role="button" class="btn btn-primary" data-toggle="modal">Launch other modal 1</a> -->
                            <!-- <a href="#myModal2" role="button" class="btn btn-primary" data-toggle="modal">Launch other modal 2</a> -->
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
            </div>

            <div id="myModalViewFile" class="modal fade modal-child" data-backdrop-limit="1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="max-height: 100vh;">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header" style="height:5px">
                            <button type="button" class="close text-danger" style="margin-top:-20px;" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <iframe src="" id="frameviewuploadfile" frameborder="0" height="100%" width="100%"></iframe>  
                            <!-- <embed src="" id="frameviewuploadfile" type="application/pdf" height="100%" width="100%" /> -->
                        </div>
                        <div class="modal-footer" style="height:5px">
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- /.card -->
    </div> <!-- <div class="col-12"> -->
</div>

<!-- DataTables  & Plugins -->
<script src="plugins/sweetalert2/sweetalert.min.js"></script>
<!-- Password Toggle -->
<!-- <script src="plugins/bootstrap-pwtoggle/jquery.min.js"></script> -->
<script src="plugins/bootstrap-pwtoggle/bootstrap.min.js"></script>
<script src="plugins/bootstrap-pwtoggle/bootstrap-show-password.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<script>
    function saverequest() {
        //Put in variable tsave value of input including the validation of department(select elements) and checkbox to assign value if check or uncheck
        var itrequestdate = $('#itrequestdate').val();
        var itdepartmentfrom = $('#itdepartmentfrom').attr('name'); 
        
        // Validate Department
        document.getElementById("departmentUserErr").innerHTML = "";
        var select = document.getElementById('selectdepartment');
        var option = select.options[select.selectedIndex];
        if (option.value=="") {
            var txt = " [required field]";
            document.getElementById("departmentUserErr").innerHTML = " " + txt.bold();  
        }
        var itdepartmentto = option.value;
        var taremarks = $('#taremarks').val();
        var fileupload = $('#itview_upload_file').val();
        //Disable this if the system will allow without saving a documents
        if (fileupload=="") {
            swal({
                title: "Need to upload PDF file before saving!",
                button: "OK",
            });
            return;  
        } 
        var fileupload = $('#itview_upload_file')[0].files[0];

        if (txt == " [required field]") {
            return;
        }
        
        if (itdepartmentfrom==itdepartmentto) {
            swal({
                title: "You cannot request with the same department!",
                button: "OK",
            });
            return;
        }

        var fd = new FormData();
        fd.append('save_request',"Y")
        fd.append('itrequestdate',itrequestdate) 
        fd.append('itdepartmentfrom',itdepartmentfrom);
        fd.append('itdepartmentto',itdepartmentto);
        fd.append('taremarks',taremarks);
        fd.append('fileupload',fileupload);

        $.ajax({
            url: 'crud.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
              if (response.match(/Your request.*/)) {
                // swal({
                //   title: response,
                //   icon: "success",
                //   button: "OK",
                // });
                swal({
                    title: response,
                    text: "Do you want to add another request?",
                    icon: "success",
                    buttons: ["Yes", "No"],
                    })
                    .then((value) => {
                    if (value==true) {
                        $("#close-btn").click();
                    } else {
                        var mySelect = document.getElementById('selectdepartment');
                        mySelect.selectedIndex = 0; 
                        document.getElementById('taremarks').value = "";
                        $("#itview_upload_file").val('');
                    }
                    });
              } else {
                swal({
                  title: response,
                  button: "OK",
                });
              }          
            },
        });
    };
    
    $('#modalHeaderUser').html('Create Request Posting'); //Changing the Modal Header Name
    $('#modal-user').modal('toggle');
    
    // // focust on User name when modal pop up setTimeout the time to "fade in" the modal doesn’t let you set focus on input. You need to create a delay
    // setTimeout(function (){
    //     $('#itpassword').focus();
    // }, 1000);

    function view_upload_filefn() {
        // alert('');
        document.getElementById('frameviewuploadfile').src = URL.createObjectURL(document.getElementById('itview_upload_file').files[0])
        $('#myModalViewFile').modal('toggle');
    };
</script>