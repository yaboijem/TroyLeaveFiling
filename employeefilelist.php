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
            <h3 class="card-title">Employee File</h3>
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
                    <label>Employee</label>
                    <span class="requiredfield" id="employeefileUserErr"><b></b></span>
                    <select name="employeefile" id="selectemployeefile" class="form-control mb-2">
                      <option value="" selected disabled hidden>Select Here...</option>
                      <?php  
                        require_once "dbconnect.php";
                        $sql = "SELECT * FROM employeefiletbl WHERE idno = '".$_SESSION['idno']."' order by employeename Asc";
                        if($result = mysqli_query($conn, $sql)){
                          if(mysqli_num_rows($result) > 0){
                            while($row = mysqli_fetch_array($result)){
                              echo '<option value="'.$row['leavecode'].'">'.$row['employeename'].'</option>';
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
                    <label>Employee Request For</label>
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
                    <label>Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="500" placeholder="Enter remarks here..."></textarea>
                  </div>
                </div>
                <div class="modal-footer justify-content-between">
                  <button type="button" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
                  <button type="button" onclick="clickbtnEmployee()" class="btn btn-success" id="btnUser">Submit</button>
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
              Employee Request File <i class="fas fa-plus"></i>
            </button> -->
            
            <div id="userprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="userprint" class="table table-bordered table-striped" style="font-size:13px;">
              <thead>
              <tr>
                <th>#</th>
                <th>ID NO.</th>
                <th>Employee Name</th>
                <th>Email</th>
                <th>Dept. Code</th>
                <th>Div. Code</th>
                <th>Approver 1</th>
                <th>Approver 2</th>
                <th>Active</th>
                <th>User Type</th>
                <?php if ($_SESSION['admin_or_user']=='admin') {
                        echo '
                <th>Reset Password</th>
                <th>VL File Less than 5 Days</th>
                <th>Action</th>';
                } ?>
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                // Attempt select query execution
                if ($_SESSION['admin_or_user']=='admin') {
                  $sql = "SELECT employeefiletbl.* FROM employeefiletbl 
                          ORDER BY idno";
                } else if ($_SESSION['admin_or_user']=='approver') {
                  $sql = "SELECT employeefiletbl.* FROM employeefiletbl 
                          WHERE approver1 ='". $_SESSION['email'] . "' OR approver2 ='". $_SESSION['email'] ."'
                          ORDER BY idno";
                }
                if($result = mysqli_query($conn, $sql)){
                    // echo "sample";
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      ++$x;
                      echo '<tr id="'.$row['idno'].'-rownumtr">
                        <td id="'.$row['idno'].'-rownum">'.$x.'</td>
                        <td id="'.$row['idno'].'-idno">'.$row['idno'].'</td>
                        <td id="'.$row['idno'].'-employeename">'.$row['employeename'].'</td>
                        <td id="'.$row['idno'].'-email">'.$row['email'].'</td>
                        <td id="'.$row['idno'].'-deptcode">'.$row['deptcode'].'</td>
                        <td id="'.$row['idno'].'-divcode">'.$row['divcode'].'</td>
                        <td id="'.$row['idno'].'-approver1">'.$row['approver1'].'</td>
                        <td id="'.$row['idno'].'-approver2">'.$row['approver2'].'</td> 
                        <td id="'.$row['idno'].'-active">'.$row['active'].'</td>
                        <td id="'.$row['idno'].'-usertype">'.$row['usertype'].'</td>';
                      
                      if ($_SESSION['admin_or_user']=='admin') {
                        echo '<td id="'.$row['idno'].'-resetpw">'.
                        '<button class="btn btn-warning" type="button" id="'.$row['idno'].'" title="Reset Password" onclick=\'validateResetPassword(this.id)\'><i class="fas fa-key"></i>
                        </button></td>';

                        //open yung employeefiletbl para i check kung meron allowvlfile na record tapos magkulay blue at mailalagay yung time stamp sa button kung meron na otherwise kulay green at walang time stamp
                        $sqlCheckAllowVL = "SELECT * FROM employeefiletbl WHERE idno = '".$row['idno']."' And allowvlfile IS NOT NULL";
                        $resultCheckAllowVL = mysqli_query($conn, $sqlCheckAllowVL);
                        if (mysqli_num_rows($resultCheckAllowVL) > 0) {
                            $rowCheckAllowVL = mysqli_fetch_array($resultCheckAllowVL);
                            echo '<td id="'.$row['idno'].'-allowvl">'.
                                  '<button class="btn btn-primary" type="button" id="'.$row['idno'].'" title="Allowed Filing < 5 Days" disabled><i class="fas fa-check"></i></button>' . $rowCheckAllowVL['allowvlfile'] .
                                  '</td>';
                        } else {
                            echo '<td id="'.$row['idno'].'-allowvl">'.
                                  '<button class="btn btn-success" type="button" id="'.$row['idno'].'" title="Allow Filing < 5 Days" onclick=\'validateAllowVL(this.id)\'><i class="fas fa-check"></i>
                                  </button></td>';
                        }

                        echo '
                        <td id="'.$row['idno'].'-delete">'.
                        '<button class="btn btn-danger" type="button" id="'.$row['idno'].'" title="Delete" onclick=\'validateDeleteRecord(this.id)\'><i class="fas fa-trash"></i>
                        </button></td>';
                      } 
                      echo '
                      </tr>';
                      $user_numrows = $x;
                    }
                  }
                }
              ?>
  
              <tfoot>
                <tr>
                  <th>#</th>
                  <th>ID NO.</th>
                  <th>Employee Name</th>
                  <th>Email</th>
                  <th>Dept. Code</th>
                  <th>Div. Code</th>
                  <th>Approver 1</th>
                  <th>Approver 2</th>
                  <th>Active</th>
                  <th>User Type</th>
                  <?php if ($_SESSION['admin_or_user']=='admin') {
                        echo '
                  <th>Reset Password</th>
                  <th>VL File Less than 5 Days</th>
                  <th>Action</th>
                  ';
                  } ?>
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
        
        function clickbtnEmployee() {
          // alert(0);
          // get the value of h4 modal header if updating or saving
          var str = $('#modalHeaderUser').html();
          var res = str.toUpperCase();
          straction = "add";
          
          var select = document.getElementById('selectemployeefile');
          var varSelectEmployeeFile = select.options[select.selectedIndex];
          
          var varEmployeeAllowance = $('#leaveAllowance').val();
          var varEmployeeUsed = $('#leaveUsed').val();
          var varEmployeeBalance = $('#leaveBalance').val();

          var select = document.getElementById('selectleaverequestfor');
          var varEmployeeRequestFor = select.options[select.selectedIndex];

          var varFromDate = $('#itfromdate').val();
          var varToDate = $('#ittodate').val();
          var varTotalDays = $('#totalDays').val();
          var varTotalHours = $('#totalHours').val();
          var varRemarks = $('#remarks').val();
          
          var fd = new FormData();
          fd.append('leave_action',straction) //for crud.php validation
          fd.append('selectEmployeeCode',varSelectEmployeeFile.value);
          fd.append('selectEmployeeName',varSelectEmployeeFile.text);
          fd.append('leaveAllowance',varEmployeeAllowance);
          fd.append('leaveUsed',varEmployeeUsed);
          fd.append('leaveBalance',varEmployeeBalance);
          fd.append('leaveRequestFor',varEmployeeRequestFor.value);
          fd.append('fromDate',varFromDate);
          fd.append('toDate',varToDate);
          fd.append('totalDays',varTotalDays);
          fd.append('totalHours',varTotalHours);
          fd.append('remarks',varRemarks);
          
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
                  message = "Employee request has been successfully submitted!";
                  $('#close-btn').click(); 
                  loadfile('employeefile');
                  loadfile('employeeemployeefile')
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
                // alert(3);
                swal({
                  title: "Employee request has been successfully submitted!",
                  icon: "success",
                  button: "OK",
                });
                $('#close-btn').click(); 
                loadfile('employeefile');
                loadfile('employeeemployeefile')
                //fillUpFromToDate();
              }
            },
              complete: function() {
                hideModalLoading();
            }
          });
        };
  
        // focust on User name when modal pop up
        $('#modal-User').on('shown.bs.modal', function () {
          $('#itusername').focus();
        });
  
        // use one modal in page for add and update
        //add
        showCreateModal = function(strid){
          $('#modalHeaderUser').html('Employee Request File'); //Changing the Modal Header Name
          $('#modal-User').modal('toggle');
  
          var table= $('#userprint').DataTable();
          //table.column( 2 ).visible( true ); //Need to visible this to get the value
          var hsleavecode = $('#'+ strid + "-leavecode").html();
          document.getElementById("selectemployeefile").value = hsleavecode;
          document.getElementById("selectemployeefile").disabled = true;

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
          
          document.getElementById("spanuserpassword").textContent = "(Employee Blank to Retain the Existing)";
        }
        

        function validateAllowVL(strid) {
          swal({
              title: "Are you sure to allow this employee to file leave less than 5 days ["+$('#'+ strid + "-employeename").html()+"]?",
              text: "Once allowed, the employee will be able to file leave less than 5 days!",
              icon: "warning",
              buttons: true,
              dangerMode: true,
          })
          
          .then((willAllow) => {
          if (willAllow) {
            var fd = new FormData();
            
            fd.append('employee_allowvl','Yes') //for crud.php validation
            fd.append('idno',strid);
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
                
                //After ma allow mag iiba yung kulay ng button to blue na ibig sabihin na allowed na mag file ng leave less than 5 days
                //pwedi ba sa ilalim ng button ay yung current time na inallowed mag file ng leave less than 5 days
                $('#'+ strid + "-allowvl").html(
                  '<button class="btn btn-primary" type="button" id="'+strid+'" title="Allowed Filing < 5 Days" disabled><i class="fas fa-check"></i></button>' + new Date().toLocaleString()
                );
              },
            });  
          } 
          });
        }


        function validateDeleteRecord(strid) {
          // alert('for delete'+strid);
          swal({
              title: "Are you sure to delete this record ["+$('#'+ strid + "-employeename").html()+"]?",
              text: "Once deleted, you will not be able to recover or undo this action!",
              icon: "warning",
              buttons: true,
              dangerMode: true,
          })
          
          .then((willDelete) => {
          if (willDelete) {
            var fd = new FormData();
            
            fd.append('employee_delete','Yes') //for crud.php validation
            fd.append('idno',strid);
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

        function validateResetPassword(strid) {
          swal({
              title: "Are you sure to reset the password of this record ["+$('#'+ strid + "-employeename").html()+"]?",
              text: "Once reset, the password will be set to default and the employee will be notified via email!",
              icon: "warning",
              buttons: true,
              dangerMode: true,
          })
          
          .then((willReset) => {
          if (willReset) {
            var fd = new FormData();
            
            fd.append('employee_resetpw','Yes') //for crud.php validation
            fd.append('email', $('#'+ strid + "-email").html());
            fd.append('employeename', $('#'+ strid + "-employeename").html());
            fd.append('idno',strid);
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
                swal({
                  title: response,
                  icon: "success",
                  button: "OK",
                });
              }, 
              complete: function() {
                hideModalLoading();
              }
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

        function updateEmployeeType() {
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
          fromLabel.textContent = "Employee Date";
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

      document.getElementById("selectleaverequestfor").addEventListener("change", updateEmployeeType);
      document.getElementById("itfromdate").addEventListener("change", computeDays);
      document.getElementById("ittodate").addEventListener("change", computeDays);

      // Initial setup
      updateEmployeeType();
    </script>