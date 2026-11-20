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
            <h3 class="card-title">Leave Filed History List</h3>
          </div>
  
          <div class="card-body">
            <div id="employeeleavefileprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="employeeleavefileprint" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>#</th>
                <th>Request No</th>
                <th>Request Date</th>
                <th>Employee Name</th>
                <th>Leave Code</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>No. of Day(s)</th>
                <th>Reason/Replacement</th>
                <th>Approver 1</th>
                <th>Approver 2</th>
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                $_SESSION['listRequestNo'] = [];
                // Attempt select query execution
                $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,employeefiletbl.approver1 as approver1_pending,employeefiletbl.approver2 as approver2_pending FROM leavefilingfiletbl 
                        LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                        WHERE leavefilingfiletbl.idno = '".$_SESSION['idno']."' 
                        AND approver1status <> 2 AND approver2status <> 2 order by approver1status, tranno";
                //echo $sql;
                if($result = mysqli_query($conn, $sql)){
                    echo $_SESSION['idno'];
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      $_SESSION['listRequestNo'][] = $row['tranno'];
                      ++$x;
                      echo '<tr id="'.$row['tranno'].'-rownumtr">
                        <td id="'.$row['tranno'].'-rownum">'.$x.'</td>
                        <td id="'.$row['tranno'].'-requestno">'.$row['id'].'</td>
                        <td id="'.$row['tranno'].'-requestdate">'.$row['trandate'].'</td>
                        <td id="'.$row['tranno'].'-employee">'.$row['employeename'].'</td>
                        <td id="'.$row['tranno'].'-leavecode">'.$row['leavecode'].'</td>
                        <td id="'.$row['tranno'].'-fromdate">'.$row['fromdate'].'</td>
                        <td id="'.$row['tranno'].'-todate">'.$row['todate'].'</td>
                        <td id="'.$row['tranno'].'-noofdays">'.$row['noofdays'].'</td>
                        <td id="'.$row['tranno'].'-remarks">'.$row['remark'].'</td>
                        <td id="'.$row['tranno'].'-ap1">'; 
                        if ($row['approver1status'] == 1) {
                            echo '<span style="color: green;">Approved</span>';
                        } else if ($row['approver1status'] == 2) { 
                            echo '<span style="color: red;">Disapproved</span>';
                        } else {
                            echo '<span style="color: orange;">Pending</span><br>
                                  <button class="btn btn-sm btn-info mt-1" 
                                          onclick="followUpLeave(\''.$row['id'].'\', \''.$row['approver1_pending'].'\')">
                                    Follow-up
                                  </button>';
                        }
                        echo '</td>
                        <td id="'.$row['tranno'].'-ap2">'; 
                        if ($row['approver2status'] == 1) {
                            echo '<span style="color: green;">Approved</span>';
                        } else if ($row['approver2status'] == 2) { 
                            echo '<span style="color: red;">Disapproved</span>';
                        } else {
                          if ($row['approver1status'] == 1) {  // Approver 1 dapat na approved bago mag follow-up sa Approver 2
                            echo '<span style="color: orange;">Pending</span><br>
                                  <button class="btn btn-sm btn-info mt-1" 
                                          onclick="followUpLeave(\''.$row['id'].'\', \''.$row['approver2_pending'].'\')">
                                    Follow-up
                                  </button>';
                          }
                        }
                        echo '</td>
                      </tr>';
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
                  <th>Employee Name</th>
                  <th>Leave Code</th>
                  <th>From Date</th>
                  <th>To Date</th>
                  <th>No. of Day(s)</th>
                  <th>Reason/Replacement</th>
                  <th>Approver 1</th>
                  <th>Approver 2</th>
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
          var table = $('#employeeleavefileprint').DataTable({
              stateSave: true,
              "bDestroy": true,
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
                
              ], "pageLength": 10,"autoWidth": false,"scrollX": true//,
              // language: {
              //   lengthMenu: "Show&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_MENU_ entries"
              // }
          });
          table.buttons().container().appendTo($('#employeeleavefileprintbar'));
        });
        
        // focust on User name when modal pop up
        $('#modal-User').on('shown.bs.modal', function () {
          $('#itusername').focus();
        });

        function followUpLeave(tranno, approverEmail) {
          swal({
              title: "Send Follow-up?",
              text: "Are you sure you want to send a follow-up email to the approver [" + approverEmail + "]?",
              icon: "warning",
              buttons: ["No", "Yes"],
              dangerMode: false,
          }).then((willProceed) => {

              if (!willProceed) {
                  return;
              }

              var fd = new FormData();
              fd.append('follow_up_leave', 'Yes');
              fd.append('tranno', tranno);
              fd.append('approverEmail', approverEmail);
              $.ajax({
                  url: "crud.php",
                  type: "POST",
                  data: fd,
                  processData: false,
                  contentType: false,
                  dataType: "text",
                  beforeSend: function() {
                    showModalLoading();
                  },
                  success: function(response) {
                  if (response.trim() === 'success') {
                    swal({
                      title: "Follow-up sent successfully!",
                      text: "",
                      icon: "success",
                      buttons: 'OK',
                    });
                  } else {
                    swal({
                      title: "Failed to send follow-up.",
                      text: response,
                      icon: "error",
                      buttons: 'OK',
                    });
                  }
                },
                error: function(xhr, status, error) {
                  swal({
                    title: "An error occurred.",
                    text: error,
                    icon: "error",
                    buttons: 'OK',
                  });
                },
                  complete: function() {
                    hideModalLoading();
                }
              });
          });
        }
    </script>