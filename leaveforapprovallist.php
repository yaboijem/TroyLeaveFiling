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
            <h3 class="card-title">Leave for Approval List</h3>
          </div>
  
          <div class="card-body">
            <?php
              if ($_SESSION['admin_or_user'] == 'admin') {
                echo '
                  <div class="mb-2 font-weight-bold text-muted">
                    Filter by <span class="text-primary">Request Date</span> range:
                  </div>
                  <div class="form-inline">
                    <label for="fromDate" class="mr-2 font-weight-bold">From:</label>
                    <input type="date" name="fromDate" id="fromDate" class="form-control form-control-sm mr-3"
                          value="' . (isset($_SESSION['Get_Leave_File_Base_On_RD_FromDate']) ? $_SESSION['Get_Leave_File_Base_On_RD_FromDate'] : '') . '">

                    <label for="toDate" class="mr-2 font-weight-bold">To:</label>
                    <input type="date" name="toDate" id="toDate" class="form-control form-control-sm mr-3"
                          value="' . (isset($_SESSION['Get_Leave_File_Base_On_RD_ToDate']) ? $_SESSION['Get_Leave_File_Base_On_RD_ToDate'] : '') . '">

                    <button onclick="getLeaveFileBaseOnRD()" class="btn btn-primary btn-sm">Show</button>
                  </div>';
              }
            ?>

            <!-- <button class="btn btn-success" type="button" onclick='approveLeave("","Approve")'>
              Approve All <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-success" type="button" onclick='approveLeave("","Disapprove")'>
              Disapprove All <i class="fas fa-times text-danger"></i>
            </button> -->
            
            <div id="userprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="userprint" class="table table-bordered table-striped" style="font-size:13px;">
              <thead>
              <tr>
                <th>#</th>
                <th>Request No</th>
                <th>Request Date</th>
                <th>ID No.</th>
                <th>Employee Name</th>
                <th>Leave Code</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Balance Before Request</th>
                <th>No. of Day(s)</th>
                <th>Reason/Replacement</th>
                <th>Approver 1</th>
                <th>Approver 2</th>
                <th>Action</th>
                <th>Image</th>
              </tr>
              </thead>
              <tbody>
              <?php
                
                // Include config file
                require_once "dbconnect.php";
                $_SESSION['listRequestNo'] = [];
                // Attempt select query execution
                //Ang ipapakita lang is yung Not Complete na Approve or Disapprove yung merong pending sa approver1status or approver2status
                if ($_SESSION['admin_or_user']=='admin') {
                  if (isset($_SESSION['Get_Leave_File_Base_On_RD'])) {
                    $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                            LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                            WHERE trandate between '".$_SESSION['Get_Leave_File_Base_On_RD_FromDate']."' AND '".$_SESSION['Get_Leave_File_Base_On_RD_ToDate']."' order by id";
                            // RD - Range Date
                            unset($_SESSION['Get_Leave_File_Base_On_RD']);
                            unset($_SESSION['Get_Leave_File_Base_On_RD_FromDate']);
                            unset($_SESSION['Get_Leave_File_Base_On_RD_ToDate']);
                  } else {
                    $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                            LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                            WHERE (approver1status = '0' OR approver2status = '0') 
                            AND approver1status <> '2' AND approver2status <> '2' order by id";
                  }
                } else if ($_SESSION['admin_or_user']=='approver') {
                  $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.employeename,employeefiletbl.approver1,employeefiletbl.approver2 FROM leavefilingfiletbl 
                          LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                          WHERE ((employeefiletbl.approver1 = '".$_SESSION['email']."' AND approver1status = '0') OR 
                                (employeefiletbl.approver2 = '".$_SESSION['email']."' AND approver2status = '0'))  
                                AND approver1status <> '2' AND approver2status <> '2' order by id";
                }
                if($result = mysqli_query($conn, $sql)){
                    // echo "sample";
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;

                    include('displayed.php');

                    while($row = mysqli_fetch_array($result)){
                      $tranNo = encryptHex($row['id'], ENCRYPTION_KEY);
                      
                      $_SESSION['listRequestNo'][] = $row['id'];
                      ++$x;
                      echo '<tr id="'.$tranNo.'-rownumtr">
                        <td id="'.$tranNo.'-rownum">'.$x.'</td>
                        <td id="'.$tranNo.'-tranno">'.$row['id'].'</td>
                        <td id="'.$tranNo.'-requestdate">'.$row['trandate'].'</td>
                        <td id="'.$tranNo.'-idno">'.$row['idno'].'</td>
                        <td id="'.$tranNo.'-employeename">'.$row['employeename'].'</td>
                        <td id="'.$tranNo.'-leavecode">'.$row['leavecode'].'</td>
                        <td id="'.$tranNo.'-fromdate">'.$row['fromdate'].'</td>
                        <td id="'.$tranNo.'-todate">'.$row['todate'].'</td>
                        <td id="'.$tranNo.'-balance">'.$row['balance'].'</td>
                        <td id="'.$tranNo.'-noofdays">'.$row['noofdays'].'</td>
                        <td id="'.$tranNo.'-remarks">'.$row['remark'].'</td>
                        <td id="'.$tranNo.'-ap1">'; 

                        if ($row['approver1status'] == 1) {
                            echo '<span style="font-size: 8px;">'.$row["approver1"].'</span><span style="color: green;"> - Approved</span>';
                        } else if ($row['approver1status'] == 2) { 
                            echo '<span style="font-size: 8px;">'.$row["approver1"].'</span><span style="color: red;"> - Disapproved</span>';
                        } else {
                            echo '<span style="font-size: 8px;">'.$row["approver1"].'</span><span style="color: orange;"> - Pending</span>';
                        }

                        echo '</td>
                        <td id="'.$tranNo.'-ap2">'; 
                        if ($row['approver2status'] == 1) {
                            echo '<span style="font-size: 8px;">'.$row["approver2"].'</span><span style="color: green;"> - Approved</span>';
                        } else if ($row['approver2status'] == 2) { 
                            echo '<span style="font-size: 8px;">'.$row["approver2"].'</span><span style="color: red;"> - Disapproved</span>';
                        } else {
                            echo '<span style="font-size: 8px;">'.$row["approver2"].'</span><span style="color: orange;"> - Pending</span>';
                        }


                        //show button kapag admin ang nakalog in
                        $showButton = false;
                        if ($_SESSION['admin_or_user']=='admin') {
                            $showButton = true;
                        } else {
                            if ($_SESSION['admin_or_user']=='approver') {
                              //Check if the logged in user is approver1 or approver2 kapag approver1 ang nakalog in at
                              //yung approver1status ay 0 or pending, then show the button. At dapat kapag approver2 ang
                              //nakalog in at yung approver1status ay 0 dapat hindi maipakita yung button kasi dapat mauna muna ang approver1 bago maapprove ni approver2
                              if (!empty($row['approver1']) && !empty($row['approver2'])) {
                                if ($_SESSION['email'] === $row['approver1'] && $row['approver1status'] == 0) {
                                    $showButton = true;
                                } else if ($_SESSION['email'] === $row['approver2'] && $row['approver2status'] == 0 && $row['approver1status'] == 1) {
                                    $showButton = true;
                                }
                              }
                            }
                        }

                        
                        if ($showButton == false) {
                          echo '</td><td><span style="color: red; font-size: 10px;">(Waiting for Approver 1)</span></td>';
                        } else {
                          echo '</td> 
                          <td id="'.$tranNo.'-Action">'.
                          '<button class="btn btn-success btn-sm equal-btn mt-1" type="button" id="'.$row['id'].'" onclick=\'approveLeave(this.id,"Approve","'.$tranNo.'")\'>
                            <i class="fas fa-check" data-toggle="tooltip" data-placement="top" title="Approve"></i>
                          </button>
                          <button class="btn btn-success btn-sm equal-btn mt-1" type="button" id="'.$row['id'].'" onclick=\'approveLeave(this.id,"Disapprove","'.$tranNo.'")\'>
                            <i class="fas fa-ban text-danger" data-toggle="tooltip" data-placement="top" title="Disapprove"></i>
                          </button></td>';
                        }

                        echo '<td id="'.$tranNo.'-attachments">';
                          if (!empty($row['leaveattachments'])) {

                              $files = explode(",", $row['leaveattachments']);

                              foreach ($files as $file) {

                                  $file = trim($file);

                                  echo '
                                  <a href="uploads/leavefiles/'.$file.'" 
                                    target="_blank"
                                    style="display:block;">
                                      '.$file.'
                                  </a>';
                              }

                          } else {
                              echo '';
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
                  <th>Request No</th>
                  <th>Request Date</th>
                  <th>ID No.</th>
                  <th>Employee Name</th>
                  <th>Leave Code</th>
                  <th>From Date</th>
                  <th>To Date</th>
                  <th>Balance Before Request</th>
                  <th>No. of Day(s)</th>
                  <th>Reason/Replacement</th>
                  <th>Approver 1</th>
                  <th>Approver 2</th>
                  <th>Action</th>
                  <th>Image</th>
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

        function approveLeave(requestNo, action, etranNo) {
          requestNo = $('#'+ etranNo + "-tranno").html();
          // return;
          if (action === "Approve") {
            if (requestNo && requestNo.trim() !== "") {
              strTitle = "Do you want to Approve Leave File Request No. " + requestNo + "?";
            } else {
              strTitle = "Do you want to Approve All Leave File Request?";
            }
            
            strColor = "blue";
            varDisplay = "none";
          } else if (action === "Disapprove") {
            if (requestNo && requestNo.trim() !== "") {
              strTitle = "Do you want to Disapprove Leave File Request No. " + requestNo + "?";
            } else {
              strTitle = "Do you want to Disapprove All Leave File Request?";
            }

            strColor = "red";
            varDisplay = "block !important";
          } else {
            return;
          }

          ap1 = $('#'+ etranNo + "-ap1").html();
          ap2 = $('#'+ etranNo + "-ap2").html();
          let approverSelectHTML = "";
          let varUserType = "";
          $.ajax({
            url: 'getUserType.php',
            type: 'GET',
            contentType: false,
            processData: false,
            success: function(userType) {
              varUserType = userType.trim();
              if (userType.trim() === "1") {
                  // // alert(ap1.includes("Pending") + " " + ap2.includes("Pending"));
                  // if (ap1.includes("Pending") && ap2.includes("Pending")) {
                  //   // alert('asdf');
                    approverSelectHTML = `
                        <div style='margin-bottom:10px;'>
                          <label for='approverSelect'>Select Approver:</label><br>
                          <select id='approverSelect' style='width:100%; padding:8px; margin-top:5px;'>
                            <option value='Approver 1'>Approver 1</option>
                            <option value='Approver 2'>Approver 2</option>
                            <option value='Both'>Both</option>
                          </select>
                        </div>
                      `;
                  // } else if (ap1.includes("Pending") && ap2.includes("Approved") ) {
                  //   approverSelectHTML = `
                  //     <div style='margin-bottom:10px;'>
                  //       <label for='approverSelect'>Select Approver:</label><br>
                  //       <select id='approverSelect' style='width:100%; padding:8px; margin-top:5px;'>
                  //         <option value='Approver 1'>Approver 1</option>
                  //       </select>
                  //     </div>
                  //   `;
                  // } else if (ap1.includes("Approved") && ap2.includes("Pending") ) {
                  //   approverSelectHTML = `
                  //     <div style='margin-bottom:10px;'>
                  //       <label for='approverSelect'>Select Approver:</label><br>
                  //       <select id='approverSelect' style='width:100%; padding:8px; margin-top:5px;'>
                  //         <option value='Approver 2'>Approver 2</option>
                  //       </select>
                  //     </div>
                  //   `;
                  // } else {
                  //   approverSelectHTML = `
                  //     <div style='margin-bottom:10px;'>
                  //     </div>
                  //   `;
                  // }    
              }

              swal({
                content: {
                  element: "div",
                  attributes: {
                    innerHTML:
                        "<h2 style='color: " + strColor + ";'>" + strTitle + "</h2><br>" +
                        approverSelectHTML +
                        "<p style='display: " + varDisplay + "; text-align: left;'>" +
                          "Please provide a reason:<br>" +
                          "<input type='text' id='disapproveReason' maxlength='150' " +
                          "style='display: " + varDisplay + "; width: 100%; padding: 8px; box-sizing: border-box; margin-top: 5px;' />" +
                        "</p>"
                    // innerHTML: "<h2 style='color: "+strColor+";'>" + strTitle + "</h2><br>"+
                    //             "<p style='display: " + varDisplay + "; text-align: left;'>" +
                    //           "Please provide a reason:<br>" +
                    //           "<input type='text' id='disapproveReason' maxlength='150' " +
                    //           "style='display: " + varDisplay + "; width: 100%; padding: 8px; box-sizing: border-box; margin-top: 5px;' />" +
                    //           "</p>"
                  }
                },
                icon: "question-mark-blue.png",
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
                  var varDisapproveReason = $('#disapproveReason').val();
                  var fd = new FormData();
                  fd.append('action',action)
                  fd.append('approve_leave','Yes') //for crud.php validation
                  fd.append('requestNo',etranNo);
                  fd.append('disapproveReason',varDisapproveReason);
                  if (varUserType === "1") {
                    fd.append('approverSelect',$('#approverSelect').val());
                    // alert($('#approverSelect').val());
                  }
                  
                  // return;
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
                      //alert(response + ' yes');
                      swal({
                        title: response,
                        button: "OK",
                      });
                      getLeaveFileBaseOnRD();
                      loadfile('leaveforapproval');
                    },
                      complete: function() {
                        hideModalLoading();
                    }
                  });
                } else {
                  //console.log("Cancelled!");
                }
              });

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

        function getLeaveFileBaseOnRD() {
          var fromDate = $('#fromDate').val();
          var toDate = $('#toDate').val();

          if (!fromDate || !toDate) {
              return;
          }

          var fd = new FormData();
          fd.append('Get_Leave_File_Base_On_RD','YES'); //for crud.php validation
          fd.append('fromDate', fromDate);
          fd.append('toDate', toDate);
                    // return;
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
              // //alert(response + ' yes');
              // swal({
              //   title: response,
              //   button: "OK",
              // });
              loadfile('leaveforapproval');
            },
              complete: function() {
                hideModalLoading();
            }
          });
        }
    </script>