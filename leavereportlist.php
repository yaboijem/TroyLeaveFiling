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
            <h3 class="card-title">Leave Report</h3>
          </div>
  
          <div class="card-body">
            <div id="userprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="userprint" class="table table-bordered table-striped" style="font-size:13px;">
              <thead>
              <tr>
                <th>#</th>
                <th>Request No</th>
                <th>Request Date</th>
                <th>Division</th>
                <th>Position</th>
                <th>ID No.</th>
                <th>Employee Name</th>
                <th>Leave Code</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>No. of Day(s)</th>
                <th>Reason/Replacement</th>
                <th>Approver 1</th>
                <th>Approver 2</th>
                <?php 
                  if ($_SESSION['admin_or_user']=='admin') {
                    echo '<th>Action</th>';
                  }
                ?>
                <th>Downloaded in Local</th>  
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                $_SESSION['listRequestNo'] = [];
                // Attempt select query execution
                // $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename FROM leavefilingfiletbl 
                //         LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                //         WHERE (leavefilingfiletbl.approver1 = '".$_SESSION['email']."' OR leavefilingfiletbl.approver2 = '".$_SESSION['email']."') order by tranno";
                //Ang ipapakita lang is yung Complete na Approve or Disapprove 
                // $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                //         LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                //         WHERE (employeefiletbl.approver1 = '".$_SESSION['email']."' OR employeefiletbl.approver2 = '".$_SESSION['email']."') 
                //         AND NOT (leavefilingfiletbl.approver1 = '".$_SESSION['email']."' 
                //         AND leavefilingfiletbl.approver2 = '".$_SESSION['email']."') 
                //         AND ((approver1status = '1' AND approver1status = '1') OR 
                //              (approver1status = '2' AND approver1status = '2') OR
                //              (approver1status = '1' AND approver1status = '2') OR
                //              (approver1status = '2' AND approver1status = '1')) order by tranno";
                if ($_SESSION['admin_or_user']=='admin') {
                  $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                          LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                          LEFT OUTER JOIN divisionfiletbl on employeefiletbl.divcode = divisionfiletbl.divcode
                          order by id";
                } else {
                  $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                          LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                          LEFT OUTER JOIN divisionfiletbl on employeefiletbl.divcode = divisionfiletbl.divcode
                          WHERE (employeefiletbl.approver1 = '".$_SESSION['email']."' AND (approver1status = '1' or approver1status = '2' or approver1status = '3')) OR 
                                (employeefiletbl.approver2 = '".$_SESSION['email']."' AND (approver2status = '1' or approver2status = '2' or approver2status = '3')) 
                          order by id";
                }
                if($result = mysqli_query($conn, $sql)){
                    // echo "sample";
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      $_SESSION['listRequestNo'][] = $row['tranno'];
                      $tranno = $row['id'];
                      ++$x;
                      echo '<tr id="'.$tranno.'-rownumtr">
                        <td id="'.$tranno.'-rownum">'.$x.'</td>
                        <td id="'.$tranno.'-requestno">'.$tranno.'</td>
                        <td id="'.$tranno.'-requestdate">'.$row['trandate'].'</td>
                        <td id="'.$tranno.'-division">'.$row['divcode'].'</td>
                        <td id="'.$tranno.'-position">'.$row['position'].'</td>
                        <td id="'.$tranno.'-idno">'.$row['idno'].'</td>
                        <td id="'.$tranno.'-employee">'.$row['employeename'].'</td>
                        <td id="'.$tranno.'-leavecode">'.$row['leavecode'].'</td>
                        <td id="'.$tranno.'-fromdate">'.$row['fromdate'].'</td>
                        <td id="'.$tranno.'-todate">'.$row['todate'].'</td>
                        <td id="'.$tranno.'-noofdays">'.$row['noofdays'].'</td>
                        <td id="'.$tranno.'-remarks">'.$row['remark'].'</td>
                        <td id="'.$tranno.'-ap1">'; 
                        if ($row['approver1status'] == 1) {
                            echo '<span style="color: green;">Approved</span>';
                        } else if ($row['approver1status'] == 2) { 
                            echo '<span style="color: red;">Disapproved</span>';
                        } else if ($row['approver1status'] == 3) { 
                            echo '<span style="color: red;">Cancelled</span>';
                        } else {
                            echo '<span style="color: orange;">Pending</span>';
                        }
                        echo '</td>
                        <td id="'.$tranno.'-ap2">'; 
                        if ($row['approver2status'] == 1) {
                            echo '<span style="color: green;">Approved</span>';
                        } else if ($row['approver2status'] == 2) { 
                            echo '<span style="color: red;">Disapproved</span>';
                        } else if ($row['approver2status'] == 3) { 
                            echo '<span style="color: red;">Cancelled</span>';
                        } else {
                            echo '<span style="color: orange;">Pending</span>';
                        }
                        echo '</td>';
                        
                        if ($_SESSION['admin_or_user']=='admin') {
                          if ($row['approver1status'] == 1 && $row['approver2status'] == 1) { 
                                echo ' 
                                <td id="'.$tranno.'-Action">'.
                                '<button class="btn btn-danger btn-sm equal-btn mt-1" type="button" id="'.$tranno.'" onclick=\'cancelLeave(this.id,"'.$tranno.'")\'>
                                  <i class="fas fa-ban text-white" data-toggle="tooltip" data-placement="top" title="Cancel"></i>
                                </button></td>';
                          } else {
                            echo '<td id="'.$tranno.'-Action"></td>';
                          }
                        }  

                        //test kung yung donwload na field galing sa leavefilingfiletbl ay value ay 1
                        //dapat ang lilitaw ay check icon kung 1 at blank lang kung 0 or Null
                        echo '<td id="'.$tranno.'-downloaded">';
                        if ($row['download'] == 1) {
                            echo '[YES]'; //<i class="fas fa-check text-success"></i>
                        } else {
                            echo '[NO]'; //blank
                        }
                        echo '</td></tr>';
                  
                      $user_numrows = $x;
                    }
                  }
                }
              ?>
  
              <tfoot>
                <tr>
                  <th>#</th>
                  <th>Request No</th>
                  <th>Request Date</th>
                  <th>Division</th>
                  <th>Position</th>
                  <th>ID No.</th>
                  <th>Employee Name</th>
                  <th>Leave Code</th>
                  <th>From Date</th>
                  <th>To Date</th>
                  <th>No. of Day(s)</th>
                  <th>Reason/Replacement</th>
                  <th>Approver 1</th>
                  <th>Approver 2</th>
                  <th>Downloaded in Local</th>
                  <?php 
                    if ($_SESSION['admin_or_user']=='admin') {
                      echo '<th>Action</th>';
                    }
                  ?>
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
              buttons: [
                  {
                      extend: 'excelHtml5',
                      text: '<i class="fas fa-file-excel"></i> Excel',
                      titleAttr: 'Excel',
                      title: 'Leave Report'
                  },
                  {
                      extend: 'pdfHtml5',
                      text: '<i class="fas fa-file-pdf"></i> PDF',
                      titleAttr: 'PDF',
                      exportOptions: {
                        columns: [ 0, 1, 2, 4, 5 ]
                      },
                      title: 'Leave Report'
                  },
                  {
                      extend: 'print',
                      text: '<i class="fas fa-print "></i> Print',
                      titleAttr: 'Print',
                      title: 'Leave Report'
                  }
              ],
              columnDefs: [
                { orderable: false, targets: -1 },
                
              ], "pageLength": 10,"autoWidth": false,"scrollX": true//,
              // language: {
              //   lengthMenu: "Show&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_MENU_ entries"
              // }
          });
          table.buttons().container().appendTo($('#userprintbar'));
        });
        
        function clickbtnLeave() {
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
          fd.append('selectLeaveFile',varSelectLeaveFile.value);
          fd.append('leaveAllowance',varLeaveAllowance);
          fd.append('leaveUsed',varLeaveUsed);
          fd.append('leaveBalance',varLeaveBalance);
          fd.append('leaveRequestFor',varLeaveRequestFor.value);
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
            success: function(response){
              // alert(response.substring(0));
              if ($.trim(response.substring(0,1)) == "1" || $.trim(response.substring(0,1)) == "2") {
                swal({
                  title: response.substring(1),
                  icon: "warning",
                  button: "OK",
                });
              } else {
                swal({
                  title: response,
                  icon: "success",
                  button: "OK",
                });
                $('#close-btn').click(); 
                loadfile('leavefile');
                fillUpFromToDate()
              }
            },
          });
        };
  
        // focust on User name when modal pop up
        $('#modal-User').on('shown.bs.modal', function () {
          $('#itusername').focus();
        });

        function cancelLeave(requestNo) {
          
          if (requestNo && requestNo.trim() !== "") {
            strTitle = "Do you want to Cancel this Leave File Request No. " + requestNo + "?";
            varDisplay = "block !important";
          }

          strColor = "red";
          swal({
            content: {
              element: "div",
              attributes: {
                innerHTML:
                  "<h2 style='color: " + strColor + ";'>" + strTitle + "</h2><br>" + 
                  "<p style='display: " + varDisplay + "; text-align: left;'>" +
                    "Please provide a reason:<br>" +
                    "<input type='text' id='disapproveReason' maxlength='150' " +
                    "style='display: " + varDisplay + "; width: 100%; padding: 8px; box-sizing: border-box; margin-top: 5px;' />" +
                  "</p>"
              }
            },
            icon: "info",
            dangerMode: false,
            buttons: {
              cancel: {
                text: "No",
                visible: true,
                closeModal: true,
              },
              confirm: {
                text: "Yes",
                closeModal: true
              }
            }
          })
          .then((willConfirm) => {
            if (willConfirm) {
              var fd = new FormData();
              var varCancelReason = $('#disapproveReason').val();
              fd.append('cancelReason',varCancelReason);
              fd.append('requestNo',requestNo);
              fd.append('cancel_leave','Yes') //for crud.php validation
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
                  loadfile('leaveforapproval');
                },
              });
            } else {
              //console.log("Cancelled!");
            }
          });
          
          // if (str === "Approve All") {
          //     strTitle = "Do you want to Approve All Leave File Request"
          // } else if (str === "") {
          //     strTitle = "Do you want to Approve Selected Leave File Request"
          // } else {
          //   return;
          // }
          // swal({
          //     title: strTitle,
          //     icon: "info",
          //     buttons: true,
          //     dangerMode: true,
          // })
          
          // .then((willDelete) => {
          // if (willDelete) {
          //   var fd = new FormData();
            
          //   fd.append('user_delete','Yes') //for crud.php validation
          //   fd.append('userid',strid);
          //   $.ajax({
          //     url: 'crud.php',
          //     type: 'post',
          //     data: fd,
          //     contentType: false,
          //     processData: false,
          //     success: function(response){
          //       swal({
          //         title: response,
          //         button: "OK",
          //       });
                
          //       //Remove the row delete in display
          //       var t = $('#userprint').DataTable();
          //       t.row("#"+strid+'-rownumtr').remove().draw();
          //     },
          //   });  
          // } 
          // });
        }
    </script>