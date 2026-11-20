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
            <h3 class="card-title">Leave Balance Report</h3>
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
                    <label>Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="500" placeholder="Enter remarks here..."></textarea>
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
                <th>Division</th>
                <th>Position</th>
                <th>ID No.</th>
                <th>Employee Name</th>
                <th>Leave Code</th>
                <th>Leave Name</th>
                <th>Allowance</th>
                <th>Used</th>
                <!-- <th>Closed Request</th> -->
                <th>Balance</th>
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                // Attempt select query execution
                if ($_SESSION['admin_or_user']=='admin') {
                  $sql = "SELECT leaveallowancefiletbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position FROM leaveallowancefiletbl
                          INNER JOIN employeefiletbl on leaveallowancefiletbl.idno = employeefiletbl.idno
                          LEFT OUTER JOIN divisionfiletbl on employeefiletbl.divcode = divisionfiletbl.divcode
                          order by employeefiletbl.idno,leavecode";
                } else {
                  $sql = "SELECT leaveallowancefiletbl.*,employeefiletbl.employeename,divisionfiletbl.divcode,employeefiletbl.position,
                        employeefiletbl.approver1,employeefiletbl.approver2 FROM leaveallowancefiletbl
                        INNER JOIN employeefiletbl on leaveallowancefiletbl.idno = employeefiletbl.idno
                        LEFT OUTER JOIN divisionfiletbl on employeefiletbl.divcode = divisionfiletbl.divcode
                        WHERE employeefiletbl.approver1 ='". $_SESSION['email'] . "' OR employeefiletbl.approver2 ='". $_SESSION['email'] ."'
                        order by employeefiletbl.idno,leavecode";
                }
                if($result = mysqli_query($conn, $sql)){
                    // echo "sample";
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      ++$x;
                      $leaveCode = str_replace('/', '_', $row['leavecode']);
                      
                      echo '<tr id="'.$leaveCode.'-rownumtr">
                        <td id="'.$leaveCode.'-rownum">'.$x.'</td>
                        <td id="'.$leaveCode.'-division">'.$row['divcode'].'</td>
                        <td id="'.$leaveCode.'-position">'.$row['position'].'</td>
                        <td id="'.$leaveCode.'-idno">'.$row['idno'].'</td>
                        <td id="'.$leaveCode.'-employeename">'.$row['employeename'].'</td>
                        <td id="'.$leaveCode.'-leavecode">'.$leaveCode.'</td>
                        <td id="'.$leaveCode.'-leavename">'.$row['leavename'].'</td>
                        <td id="'.$leaveCode.'-allowance">'.$row['allowance'].'</td>
                        <td id="'.$leaveCode.'-use">'.$row['use'].'</td>
                        <td id="'.$leaveCode.'-balance">'.$row['balance'].'</td>
                      </tr>';
                      $user_numrows = $x;
                    }
                  }
                }
              ?>
  
              <tfoot>
                <tr>
                  <th>#</th>
                  <th>Division</th>
                  <th>Position</th>
                  <th>ID No.</th>
                  <th>Employee Name</th>
                  <th>Leave Code</th>
                  <th>Leave Name</th>
                  <th>Allowance</th>
                  <th>Used</th>
                  <!-- <th>Closed Request</th> -->
                  <th>Balance</th>
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
                    title: 'Leave Balance Report'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    titleAttr: 'PDF',
                    exportOptions: {
                      columns: [ 0, 1, 2, 4, 5 ]
                    },
                    title: 'Leave Balance Report'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print "></i> Print',
                    titleAttr: 'Print',
                    title: 'Leave Balance Report'
                }
            ],
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
  </script>