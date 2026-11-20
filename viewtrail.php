<?php @ob_start(); if (!isset($_SESSION)) {session_start();} ?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <!-- /.card-header -->
      <div class="card-body">
          <table id="view_trail_table" class="table table-bordered table-striped border border-dark">
            <thead>
            <tr>
              <th>ADV No.</th> <!-- Hash Display -->
              <th>ADV Date</th>
              <th>ADV Time</th>
              <th>User</th> <!-- Hash Display -->
              <th>User Department</th>
              <th>Verify Department</th>
              <th>Reason/Replacement</th>
              <th>Status</th>
            </tr>
            </thead>
            <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                $pagedisplayfor = $_SESSION['admin_or_user'];
                $reqno = $_GET['req_no'];

                // Attempt select query execution
                if ($pagedisplayfor=='admin') {
                  $sql = "SELECT approvalfiletbl.*,deptA.deptname as deptnamefrom,deptB.deptname as deptnameto,userfiletbl.username FROM approvalfiletbl 
                        Left Outer Join departmentfiletbl as deptA on approvalfiletbl.appdeptid = deptA.deptid 
                        Left Outer Join departmentfiletbl as deptB on approvalfiletbl.verifytodept = deptB.deptid 
                        Left Outer Join userfiletbl on approvalfiletbl.appuserid = userfiletbl.userid 
                        Where approvalfiletbl.reqno = $reqno order by appno ASC";
                } else {
                  $sql = "SELECT approvalfiletbl.*,deptA.deptname as deptnamefrom,deptB.deptname as deptnameto,userfiletbl.username FROM approvalfiletbl 
                        Left Outer Join departmentfiletbl as deptA on approvalfiletbl.appdeptid = deptA.deptid 
                        Left Outer Join departmentfiletbl as deptB on approvalfiletbl.verifytodept = deptB.deptid 
                        Left Outer Join userfiletbl on approvalfiletbl.appuserid = userfiletbl.userid 
                        Where approvalfiletbl.reqno = $reqno order by appno ASC";
                }
                if($result = mysqli_query($conn, $sql)){
                  if(mysqli_num_rows($result) > 0){
                    while($row = mysqli_fetch_array($result)){
                      if ($row['status'] == "A") {
                          $statustitle= "Approved";
                      } else if ($row['status'] == "D") {
                        $statustitle= "Disapproved";
                      } else if ($row['status'] == "V") {
                        $statustitle= "Verify";
                      }
                      echo '<tr id="'.$row['appno'].'-rownumtr">';
                      if ($pagedisplayfor=='admin') {
                        echo '<td class="" id="'.$row['appno'].'-appno">'.substr(sha1($row['appno']),0,12).'</td>
                        <td class="" id="'.$row['appdate'].'-date">'.substr(sha1($row['appdate']),0,12).'</td>
                        <td class="" id="'.$row['apptime'].'-time">'.substr(sha1(date_format(date_create($row['apptime']),"h:i:s A")),0,12).'</td>';
                      } else {
                        echo '<td class="" id="'.$row['appno'].'-appno">'.$row['appno'].'</td>
                        <td class="" id="'.$row['appdate'].'-date">'.$row['appdate'].'</td>
                        <td class="" id="'.$row['apptime'].'-time">'.date_format(date_create($row['apptime']),"h:i:s A").'</td>';
                      }
                      echo '<td class="" id="'.$row['username'].'-name">'.$row['username'].'</td>
                        <td class="" id="'.$row['appdeptid'].'-deptfrom">'.$row['deptnamefrom'].'</td>
                        <td class="" id="'.$row['verifytodept'].'-deptto">'.$row['deptnameto'].'</td>';
                      if ($pagedisplayfor=='admin') {
                        echo '<td class="" id="'.$row['remarks'].'-deptto">'.substr(sha1($row['remarks']),0,50).'</td>';
                      } else {
                        echo '<td class="" id="'.$row['remarks'].'-deptto">'.$row['remarks'].'</td>';
                      }
                      echo '<td class="" id="'.$row['status'].'-status" title="'.$statustitle.'">'.$row['status'].'</td>
                      </tr>';
                    }
                  }
                }
              ?>
            </tbody>
            <tfoot>
            <tr>
              <!-- <th>Request No.</th> 
              <th>Request Date</th>
              <th>User</th> 
              <th>Dept. From</th>
              <th>Dept. To</th>
              <th>Reason/Replacement</th> </th> -->
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            
        
            <div id="modal_view_open_req" class="modal fade modal-child" data-backdrop-limit="1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
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
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<!-- <script src="plugins/jszip/jszip.min.js"></script> -->
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> -->

<script>
    $(function() {
      var table = $('#view_trail_table').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bInfo": false,
        "bPaginate": false,
        "order": [[ 1, "asc" ]],
        "bDestroy": true //Prevent showing duplicate reinitialise
      });
    });
</script>