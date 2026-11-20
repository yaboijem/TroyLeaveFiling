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
            <h3 class="card-title">Leave Archive Report</h3>
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
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                $_SESSION['listRequestNo'] = [];
                if ($_SESSION['admin_or_user']=='admin') {
                  $sql = "SELECT leavefilingfilearchivetbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfilearchivetbl 
                          LEFT OUTER JOIN employeefiletbl on leavefilingfilearchivetbl.idno = employeefiletbl.idno
                          LEFT OUTER JOIN divisionfiletbl on employeefiletbl.divcode = divisionfiletbl.divcode
                          order by id";
                } else {
                  $sql = "SELECT leavefilingfilearchivetbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfilearchivetbl 
                          LEFT OUTER JOIN employeefiletbl on leavefilingfilearchivetbl.idno = employeefiletbl.idno
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
                  <th>Division</th>
                  <th>Position</th>
                  <th>ID No</th>
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
                      title: 'Leave Archive Report'
                  },
                  {
                      extend: 'pdfHtml5',
                      text: '<i class="fas fa-file-pdf"></i> PDF',
                      titleAttr: 'PDF',
                      exportOptions: {
                        columns: [ 0, 1, 2, 4, 5 ]
                      },
                      title: 'Leave Archive Report'
                  },
                  {
                      extend: 'print',
                      text: '<i class="fas fa-print "></i> Print',
                      titleAttr: 'Print',
                      title: 'Leave Archive Report'
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
        
        // focust on User name when modal pop up
        $('#modal-User').on('shown.bs.modal', function () {
          $('#itusername').focus();
        });
    </script>