<?php 
    if (!isset($_SESSION)) {session_start();}
    // echo $_SESSION['idno'];
?>

<style>
    
    </style>
  
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-primary">
            <h3 class="card-title">Leave File</h3>
          </div>
  
          <div class="modal fade" id="modal-User" data-keyboard="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                <div class="modal-header bg-primary">
                  <h4 class="modal-title" id="modalHeaderUser"></h4>
                  <button type="button" class="close" data-dismiss="modal"  aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">

                  <!-- <label id="lbluserid">ID</label>
                  <input type="text" class="form-control" id="ituserid" value="" disabled> -->
                  <div class="form-group">
                    <label>Leave</label>
                    <span class="requiredfield" id="leavefileUserErr"><b></b></span>
                    <select name="leavefile" id="selectleavefile" class="form-control mb-2">
                      <option value="" selected disabled hidden>Select Here...</option>
                      <?php  
                        require_once "dbconnect.php";
                        $sql = "SELECT * FROM leaveallowancefiletbl WHERE idno = '".$_SESSION['idno']."' order by leavename Asc";
                        if($result = mysqli_query($conn, $sql)){
                          if(mysqli_num_rows($result) > 0){
                            while($row = mysqli_fetch_array($result)){
                              $leaveCode = str_replace('/', '_', $row['leavecode']);
                              echo '<option value="'.$leaveCode.'">'.$row['leavename'].'</option>';
                            }
                          }
                        }
                      ?>  
                    </select>

                    <div class="row">
                      <div class="col-md-4">
                        <label>Allowance</label>
                        <input type="text" class="form-control" id="leaveAllowance" disabled>
                      </div>
                      <div class="col-md-4">
                        <label>Used</label>
                        <input type="text" class="form-control" id="leaveUsed" disabled>
                      </div>
                      <div class="col-md-4">
                        <label>Balance</label>
                        <input type="text" class="form-control" id="leaveBalance" disabled>
                      </div>
                    </div>
                  </div>
                  
                  <div>
                    <label>Leave Request For</label>
                    <span class="requiredfield" id="leaverequestforUserErr"><b></b></span>
                    <select name="leaverequestfor" id="selectleaverequestfor" class="form-control">
                      <option value="Days" selected>Days</option>
                      <option value="Half Day">Half Day</option>
                    </select>
                  </div>

                  <label id="fromDateLabel">From</label>
                  <span class="requiredfield" id="fromdateusererr"><b></b></span>
                  <input type="date" class="form-control" id="itfromdate" name="fromdate" value="" required>

                  <div id="toDateContainer">
                    <label>To</label>
                    <span class="requiredfield" id="todateusererr"><b></b></span>
                    <input type="date" class="form-control" id="ittodate" name="todate" value="" required>
                  </div>

                  <div class="row mt-2">
                    <div class="col-md-6" id="daysContainer">
                      <label>Total No. of Days</label>
                      <input type="number" class="form-control" id="totalDays" readonly>
                    </div>
                    <!-- <div class="col-md-6" id="hoursContainer" style="display: none;">
                      <label>No. of Hours</label>
                      <input type="number" class="form-control" id="totalHours" min="0" step="1">
                    </div> -->
                  </div>
                  
                  <div class="form-group mt-2">
                    <label>Reason/Replacement</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="500" placeholder="Enter reason/replacement here..."></textarea>
                  </div>

                  <div class="form-group mt-2" id="fileUploadContainer" style="display:none;">
                    <label>Upload Medical Certificate / Supporting Document</label>
                    
                    <input type="file" 
                          class="form-control" 
                          id="leaveFileUpload" 
                          accept=".jpg, .jpeg, .png, .heic, .webp"
                          multiple>

                    <small class="text-muted">
                      Max 3 files only • Max 2MB per file • JPG, PNG, WEBP, HEIC only
                    </small>

                    <div id="fileError" style="color:red; font-size:13px;"></div>
                  </div>
                  
                  
                </div>
                <div class="modal-footer justify-content-between">
                  <button type="button" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
                  <button type="button" onclick="clickbtnLeave()" class="btn btn-success" id="btnUser">Submit</button>
                  <!-- <button type="button" onclick="eyepw()" class="btn btn-success" id="btnUser">testing</button> -->
                  
                </div>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
          </div>
          <!-- /.modal -->
  
          <!-- /.card-header -->
          <div class="card-body">
            <!-- <a href='#' class = "btn btn-success" data-toggle="modal" data-target="#modal-User">Add New  <i class="fas fa-plus"></i></a> -->
            <!-- <input type='button' class = "btn btn-success" value='Show Create Modal' id='ShowModalButton' onclick='showCreateModal()' /> -->
            <!-- <button class="btn btn-success" type="button" onclick='showCreateModal()'>
              Leave Request File <i class="fas fa-plus"></i>
            </button> -->
            
            <div id="userprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="userprint" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>#</th>
                <th>Leave Code</th>
                <th>Leave Name</th>
                <th>Allowance</th>
                <th>Used</th>
                <!-- <th>Closed Request</th> -->
                <th>Balance</th>
                <th>Leave Request File</th>
                <!-- <th>Action</th> -->
  
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                // Attempt select query execution
                $sql = "SELECT leaveallowancefiletbl.* FROM leaveallowancefiletbl 
                        WHERE idno = '".$_SESSION['idno']."' order by leavecode";
                if($result = mysqli_query($conn, $sql)){
                    // echo "sample";
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      ++$x;
                      $leaveCode = str_replace('/', '_', $row['leavecode']);
                      
                      echo '<tr id="'.$leaveCode.'-rownumtr">
                        <td id="'.$leaveCode.'-rownum">'.$x.'</td>
                        <td id="'.$leaveCode.'-leavecode">'.$leaveCode.'</td>
                        <td id="'.$leaveCode.'-leavename">'.$row['leavename'].'</td>
                        <td id="'.$leaveCode.'-allowance">'.$row['allowance'].'</td>
                        <td id="'.$leaveCode.'-use">'.$row['use'].'</td>
                        <td id="'.$leaveCode.'-balance">'.$row['balance'].'</td>
                        <td id="'.$leaveCode.'-leaverequestfile">'.
                        '<button class="btn btn-success" type="button" id="'.$leaveCode.'" onclick=\'showCreateModal(this.id)\'><i class="fas fa-plus"></i>
            </button></td>
                      </tr>';
                      $user_numrows = $x;
                    }
                  }
                }
              ?>
  
              <tfoot>
                <tr>
                  <th>#</th>
                  <th>Leave Code</th>
                  <th>Leave Name</th>
                  <th>Allowance</th>
                  <th>Used</th>
                  <!-- <th>Closed Request</th> -->
                  <th>Balance</th>
                  <th>Leave Request File</th>
                  <!-- <th>Action</th> -->
                </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    
    <!-- DataTables  & Plugins -->
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="plugins/jszip/jszip.min.js"></script>
    <script src="plugins/pdfmake/pdfmake.min.js"></script>
    <script src="plugins/pdfmake/vfs_fonts.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
    <script src="plugins/sweetalert2/sweetalert.min.js"></script>
    <!-- Password Toggle -->
    <!-- <script src="plugins/bootstrap-pwtoggle/jquery.min.js"></script> -->
    <script src="plugins/bootstrap-pwtoggle/bootstrap.min.js"></script>
    <script src="plugins/bootstrap-pwtoggle/bootstrap-show-password.min.js"></script>
  
    <script>
        function eyepw() {
          $('.glyphicon').click();
          // alert($('.glyphicon').attr('class'));
        }

        $(function() {
          var table = $('#userprint').DataTable({
              // buttons: [
              //     {
              //         extend: 'excelHtml5',
              //         text: '<i class="fas fa-file-excel"></i> Excel',
              //         titleAttr: 'Excel'
              //     },
              //     {
              //         extend: 'pdfHtml5',
              //         text: '<i class="fas fa-file-pdf"></i> PDF',
              //         titleAttr: 'PDF',
              //         exportOptions: {
              //           columns: [ 0, 1, 2, 4, 5 ]
              //         }
              //     },
              //     {
              //         extend: 'print',
              //         text: '<i class="fas fa-print "></i> Print',
              //         titleAttr: 'Print'
              //     }
              // ],
              columnDefs: [
                { orderable: false, targets: -1 },
                
              ], "pageLength": 10,"autoWidth": false,"scrollX": true
          });
          table.buttons().container().appendTo($('#userprintbar'));

          $(window).on('resize', function () {
              var table = $('#userprint').DataTable();
              table.columns.adjust().draw();
          });

        });
        
        function clickbtnLeave() {
          // alert(0);
          // get the value of h4 modal header if updating or saving
          var str = $('#modalHeaderUser').html();
          var res = str.toUpperCase();
          straction = "add";
          
          var select = document.getElementById('selectleavefile');
          var varSelectLeaveFile = select.options[select.selectedIndex];
          
          var varLeaveAllowance = $('#leaveAllowance').val();
          var varLeaveUsed = $('#leaveUsed').val();
          var varLeaveBalance = $('#leaveBalance').val();

          var select = document.getElementById('selectleaverequestfor');
          var varLeaveRequestFor = select.options[select.selectedIndex];

          var varFromDate = $('#itfromdate').val();
          var varToDate = $('#ittodate').val();
          var varTotalDays = $('#totalDays').val();
          var varTotalHours = $('#totalHours').val();
          var varRemarks = $('#remarks').val();
          
          var fd = new FormData();
          fd.append('leave_action',straction) //for crud.php validation
          fd.append('selectLeaveCode',varSelectLeaveFile.value);
          fd.append('selectLeaveName',varSelectLeaveFile.text);
          fd.append('leaveAllowance',varLeaveAllowance);
          fd.append('leaveUsed',varLeaveUsed);
          fd.append('leaveBalance',varLeaveBalance);
          fd.append('leaveRequestFor',varLeaveRequestFor.value);
          fd.append('fromDate',varFromDate);
          fd.append('toDate',varToDate);
          fd.append('totalDays',varTotalDays);
          fd.append('totalHours',varTotalHours);
          fd.append('remarks',varRemarks);
          

          //  GET FILE INPUT
          var fileInput = document.getElementById("leaveFileUpload");

          //  CHECK kung SL
          var leaveType = varSelectLeaveFile.value.toUpperCase();

          if (leaveType.includes("SL") || leaveType.includes("EL_BL")) {

              if (fileInput.files.length === 0) {
                  alert("Please upload at least 1 required document for your leave.");
                  return;
              }
              
              //  APPEND FILES
              for (let i = 0; i < fileInput.files.length; i++) {
                  fd.append("leaveFiles[]", fileInput.files[i]);
              }
          }



          $.ajax({
            url: 'crud.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            beforeSend: function() {
              showModalLoading();
            },
            success: function(response){
              //alert(response.substring(0));
              console.log(response);
              if ($.trim(response.substring(0,1)) == "1" || $.trim(response.substring(0,1)) == "2") {
                if (response.includes("The message has been sent.")) {
                  // alert(1);
                  message = "Leave request has been successfully submitted!";
                  $('#close-btn').click(); 
                  loadfile('leavefile');
                  loadfile('employeeleavefile');
                  loadfile('employeeleavefiledisapproved');
                  //fillUpFromToDate();
                } else {
                  // alert(2);
                  message = response.substring(1);
                }
                swal({
                  title: message,
                  icon: "warning",
                  button: "OK",
                });
              } else { //if (response.includes("The message has been sent.")) {
                //alert(response);
                swal({
                  title: response, //"Leave request has been successfully submitted!",
                  icon: "success",
                  button: "OK",
                });
                $('#close-btn').click(); 
                loadfile('leavefile');
                loadfile('employeeleavefile');
                loadfile('employeeleavefiledisapproved');
                //fillUpFromToDate();
              }
            },
              complete: function() {
                hideModalLoading();
            }
          });
        };
        

        // $(document).ready(function () {

                function checkIfSL() {
                    let leaveType = $("#selectleavefile").val();
                    //alert(leaveType);
                    if (!leaveType) return;

                    // convert to uppercase para safe
                    leaveType = leaveType.toUpperCase();

                    // 👉 condition: dapat may "SL"
                    if (leaveType.includes("SL") || leaveType.includes("EL_BL")) {
                        $("#fileUploadContainer").fadeIn();
                    } else {
                        $("#fileUploadContainer").hide();
                        $("#leaveFileUpload").val(""); // reset files
                    }
                }
                
                // ✅ RUN agad pag load (IMPORTANT dahil may default value ka)
                //checkIfSL();

                // // ✅ In case magbago (kahit disabled ngayon, ready na ito)
                // $("#selectleavefile").on("change", function () {
                //     checkIfSL();
                // });

                // ✅ FILE VALIDATION (frontend only)
                $("#leaveFileUpload").on("change", function () {
                    const files = this.files;

                    if (files.length > 3) {
                        alert("Maximum of 3 files only.");
                        this.value = "";
                        return;
                    }

                    for (let i = 0; i < files.length; i++) {

                        // 2MB limit
                        if (files[i].size > 2 * 1024 * 1024) {
                            alert("Each file must be less than 2MB.");
                            this.value = "";
                            return;
                        }

                        // optional: file type check
                        const allowed = ["image/jpeg", "image/png", "image/webp", "image/heic"];

                        if (!allowed.includes(files[i].type)) {
                            alert("Invalid file type detected.");
                            this.value = "";
                            return;
                        }
                    }
                });

            // });







        // focust on User name when modal pop up
        $('#modal-User').on('shown.bs.modal', function () {
          $('#itusername').focus();
        });
  
        // use one modal in page for add and update
        //add
        showCreateModal = function(strid){
          $('#modalHeaderUser').html('Leave Request File'); //Changing the Modal Header Name
          $('#modal-User').modal('toggle');
  
          var table= $('#userprint').DataTable();
          //table.column( 2 ).visible( true ); //Need to visible this to get the value
          var hsleavecode = $('#'+ strid + "-leavecode").html();
          // var hsleavecode = document.getElementById(strid).value;
          // var hsleavecode = document.querySelector("#EL\\/BL").value;
          document.getElementById("selectleavefile").value = hsleavecode;
          checkIfSL();
          document.getElementById("selectleavefile").disabled = true;

          var allowance= $('#'+ strid + "-allowance").html();
          document.getElementById("leaveAllowance").value = allowance;

          var use= $('#'+ strid + "-use").html();
          document.getElementById("leaveUsed").value = use;

          var balance= $('#'+ strid + "-balance").html();
          document.getElementById("leaveBalance").value = balance;

          fillUpFromToDate();
        }

        //update
        showUpdateModal = function(strid){
          $('#modalHeaderUser').html('Updating User'); //Changing the Modal Header Name
          $('#modal-User').modal('toggle');
          
          //Remove the Content of Input, Select and others
          //Initialized the value of elements when add record
          document.getElementById('ituserid').value = "";
          document.getElementById('itusername').value = "";
          document.getElementById('itemail').value = "";
          var mySelect = document.getElementById('selectdepartment');
          mySelect.selectedIndex = 0; 
          document.getElementById('itpassword').value = "";
          
          //Check if the password is visible need to invisible it
          if ($('.glyphicon').attr('class')=="glyphicon icon-eye-close glyphicon-eye-close") { //Once you have submit the records that the password is visible what happen next is when you add new or update record the password will appear visible and this is the solution you need to invisible the password after submit the record
            // alert('update')
            eyepw();
          }
          $("#cbactiveuser").prop("checked", true); //Default True When Adding Record
          // $("#cbclosedrequest").prop("checked", false); //Default True When Adding Record
          
          //Put the record from datables you edit to Modal Display
          document.getElementById("lbluserid").style.display = "block";
          document.getElementById("ituserid").style.display = "block";
          document.getElementById('ituserid').value = strid;
          document.getElementById('itusername').value = $('#'+ strid + "-name").html();
          document.getElementById('itemail').value = $('#'+ strid + "-email").html();

          // Get the value of hidden id department from datatables
          // var table= $('#userprint').DataTable();
          // var hdepartmentid = table.row("#"+strid+'-rownumtr').data()[3];
          // document.getElementById("selectdepartment").value = hdepartmentid;

          var table= $('#userprint').DataTable();
          table.column( 3 ).visible( true ); //Need to visible this to get the value
          var hsdeptid = $('#'+ strid + "-deptid").html();
          // alert($('#'+ strid + "-roomtypeid").html());
          document.getElementById("selectdepartment").value = hsdeptid;
          table.column( 3 ).visible( false );


          // // document.getElementById("selectdepartment").value = $('#'+ strid + "-deptid").html(); //You can use this if the column is hidden
          // if ($('#'+ strid + "-closedrequest").html() === "YES") {
          //   $("#cbclosedrequest").prop("checked", true);
          // } else {
          //   $("#cbclosedrequest").prop("checked", false);
          // }
  
          if ($('#'+ strid + "-active").html() === "YES") {
            $("#cbactiveuser").prop("checked", true);
          } else {
            $("#cbactiveuser").prop("checked", false);
          }
          
          document.getElementById("spanuserpassword").textContent = "(Leave Blank to Retain the Existing)";
        }
        
        function validateDeleteRecord(strid) {
          // alert('for delete'+strid);
          swal({
              title: "Are you sure?",
              text: "Once deleted, you will not be able to recover or undo this action!",
              icon: "warning",
              buttons: true,
              dangerMode: true,
          })
          
          .then((willDelete) => {
          if (willDelete) {
            var fd = new FormData();
            
            fd.append('user_delete','Yes') //for crud.php validation
            fd.append('userid',strid);
            $.ajax({
              url: 'crud.php',
              type: 'post',
              data: fd,
              contentType: false,
              processData: false,
              success: function(response){
                swal({
                  title: response,
                  button: "OK",
                });
                
                //Remove the row delete in display
                var t = $('#userprint').DataTable();
                t.row("#"+strid+'-rownumtr').remove().draw();
              },
            });  
          } 
          });
        }

        function validateEmail(value) {
          var input = document.createElement('input');
          input.type = 'email';
          input.required = true;
          input.value = value;
  
          if (input.reportValidity()) {
            return 'valid';
          }else {  
            return 'invalid';
          }
        }

        function updateLeaveType() {
        const type = document.getElementById("selectleaverequestfor").value;
        const fromLabel = document.getElementById("fromDateLabel");
        const toDateContainer = document.getElementById("toDateContainer");
        const daysContainer = document.getElementById("daysContainer");
        // const hoursContainer = document.getElementById("hoursContainer");

        if (type === "Days") {
          fromLabel.textContent = "From";
          toDateContainer.style.display = "block";
          daysContainer.style.display = "block";
          // hoursContainer.style.display = "none";
          computeDays(); // Recompute if dates are already selected
        } else {
          fromLabel.textContent = "Leave Date";
          toDateContainer.style.display = "none";
          //daysContainer.style.display = "none";
          // hoursContainer.style.display = "block";
          document.getElementById("totalDays").value = ".5"; // Clear previous value
        }
      }

      function fillUpFromToDate() {
        // Get today's date
        today = new Date();
        // alert(today);
        // Add 5 days
        plus5Days = new Date();
        plus5Days.setDate(today.getDate() + 6);
        // Format as YYYY-MM-DD
        plus5DaysStr = plus5Days.toISOString().split('T')[0];
        // Set default value for both date inputs
        // alert(plus5DaysStr + "1123");
        document.getElementById('itfromdate').value = plus5DaysStr;
        document.getElementById('ittodate').value = plus5DaysStr;
        computeDays();
      }

      function computeDays() {
        const from = new Date(document.getElementById("itfromdate").value);
        const to = new Date(document.getElementById("ittodate").value);
        if (from && to && to >= from) {
          const diffTime = Math.abs(to - from);
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Inclusive
          document.getElementById("totalDays").value = diffDays;
        } else {
          document.getElementById("totalDays").value = "";
        }
      }

      document.getElementById("selectleaverequestfor").addEventListener("change", updateLeaveType);
      document.getElementById("itfromdate").addEventListener("change", computeDays);
      document.getElementById("ittodate").addEventListener("change", computeDays);

      // Initial setup
      updateLeaveType();

    </script>