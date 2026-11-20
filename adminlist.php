<style>
    
    </style>
  
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-primary">
            <h3 class="card-title">Admin List</h3>
          </div>
  
          <div class="modal fade" id="modal-admin" data-keyboard="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document"">
              <div class="modal-content">
                <div class="modal-header bg-primary">
                  <h4 class="modal-title" id="modalheaderadmin"></h4>
                  <button type="button" class="close" data-dismiss="modal"  aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <label id="lbladminid">ID</label>
                  <input type="text" class="form-control" id="itadminid" value="" disabled>
  
                  <label>Name</label>
                  <span class="requiredfield" id="nameadminErr"><b></b></span>
                  <input type="text" class="form-control" id="itadminname" value="" required>
                  
                  <label>Email</label>
                  <span class="requiredfield" id="emailadminerr"><b></b></span>
                  <input type="email" class="form-control" id="itemail" value="" required>
  
                  <form>
                    <div class="form-group">
                      <label>Password <span id="spanadminpassword" class="text-info"></span></label>
                      <span class="requiredfield" id="passwordadminErr"><b></b></span>
                      <input type="password" class="form-control" maxlength="1024" data-toggle="password" id="itpassword" placeholder="At least 6 characters" required>
                    </div>
                  </form>
                  
                  <div>
                    <input type="checkbox" id="cbactiveadmin" name="cbactiveadmin" value="Active" checked>
                    <label class="mt-1" for="activeadminfor"> Active </label><br>
                  </div>
                </div>
                <div class="modal-footer justify-content-between">
                  <button type="button" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
                  <button type="button" onclick="clickbtnadmin()" class="btn btn-success" id="btnadmin">Submit</button>
                </div>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
          </div>
          <!-- /.modal -->
  
          <!-- /.card-header -->
          <div class="card-body">
            <!-- <a href='#' class = "btn btn-success" data-toggle="modal" data-target="#modal-admin">Add New  <i class="fas fa-plus"></i></a> -->
            <!-- <input type='button' class = "btn btn-success" value='Show Create Modal' id='ShowModalButton' onclick='showCreateModal()' /> -->
            <button class="btn btn-success" type="button" onclick='showCreateModal()'>
              Add New  <i class="fas fa-plus"></i>
            </button>
            
            <div id="adminprintbar" style="float:right">
            
            </div> <br /> <br/>
            <table id="adminprint" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>#</th>
                <th>Admin Name</th>
                <th>Email</th>
                <th>Active</th>
                <th>Action</th>
  
              </tr>
              </thead>
              <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                // Attempt select query execution
                $sql = "SELECT adminfiletbl.* FROM adminfiletbl order by adminid DESC";
                if($result = mysqli_query($conn, $sql)){
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      ++$x;
                      echo '<tr id="'.$row['adminid'].'-rownumtr">
                        <td id="'.$row['adminid'].'-rownum">'.$x.'</td>
                        <td id="'.$row['adminid'].'-name">'.$row['adminname'].'</td>
                        <td id="'.$row['adminid'].'-email">'.$row['email'].'</td>';
                        echo '</td>
                        <td id="'.$row['adminid'].'-active">';
                        if ($row['active'] == 1) {
                          echo 'YES';
                        } else {
                          echo 'NO';
                        }
                        echo '</td>
                        <td>
                            <a href="#" onclick="showUpdateModal(this.id)" id="'.$row['adminid'].'" class="mr-3" title="Update Record" data-toggle="tooltip"><span class="fas fa-pen-square fa-lg"></span></a>
                            <a href="#" onclick="validateDeleteRecord(this.id)" id="'.$row['adminid'].'" class="mr-3" title="Delete Record" data-toggle="tooltip"><span class="fas fa-trash fa-lg text-danger"></span></a>
                        </td>
                      </tr>';
                      $admin_numrows = $x;
                    }
                  }
                }
              ?>
  
              <tfoot>
                <tr>
                  <th>#</th>
                  <th>Admin Name</th>
                  <th>Email</th>
                  <th>Active</th>
                  <th>Action</th>
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
        $(function() {
          // $("#oldrequestprint").DataTable({
          //     "responsive": true, "lengthChange": true, "autoWidth": false,"pageLength": 10,
          //     "buttons": ["copy", "csv", "excel", "pdf", "print"]
          // }).buttons().container().appendTo('#oldrequestprint_wrapper .col-md-6:eq(0)');
        
          var table = $('#adminprint').DataTable({
              buttons: [
                  {
                      extend: 'excelHtml5',
                      text: '<i class="fas fa-file-excel"></i> Excel',
                      titleAttr: 'Excel'
                  },
                  {
                      extend: 'pdfHtml5',
                      text: '<i class="fas fa-file-pdf"></i> PDF',
                      titleAttr: 'PDF'
                  },
                  {
                      extend: 'print',
                      text: '<i class="fas fa-print "></i> Print',
                      titleAttr: 'Print'
                  }
              ],columnDefs: [
                { orderable: false, targets: -1 }
              ], "pageLength": 5,"autoWidth": false,"scrollX": true
          });
          table.buttons().container().appendTo($('#adminprintbar'));
        });
  
        function clickbtnadmin() {
          // get the value of h4 modal header if updating or saving
          var str = $('#modalheaderadmin').html();
          var res = str.toUpperCase();
          if (res == "UPDATING ADMIN") {
            straction = "update";
          } else {
            straction = "add";
          }
          
          //Put in variable the value of input including the validation of department(select elements) and checkbox to assign value if check or uncheck
          var itadminid = $('#itadminid').val(); 
          var itadminname = $('#itadminname').val();
          var itemail = $('#itemail').val();
          var itpassword = $('#itpassword').val();
          var cbactiveadmin = $("input#cbactiveadmin");
          if (cbactiveadmin.is(":checked")) {
            cbactiveadmin = 1;
          } else { //Uncheck
            cbactiveadmin = 0;
          }
          // alert(cbclosedrequest);
          // return;
          var fd = new FormData();
          fd.append('admin_action',straction) //for crud.php validation
          fd.append('itadminid',itadminid);
          fd.append('itadminname',itadminname);
          fd.append('itemail',itemail);
          fd.append('itpassword',itpassword);
          fd.append('cbactiveadmin',cbactiveadmin);
  
          // Validate for blank input and for email wrong format and password atleast 6 character
          var txt = "";
          document.getElementById("nameadminErr").innerHTML = "";
          if (itadminname.trim()=="") {
            txt = " [required field]"
            document.getElementById("nameadminErr").innerHTML = " " + txt.bold();
          }
          document.getElementById("emailadminerr").innerHTML = "";
          if (itemail.trim()=="") {
            txt = " [required field]"
            document.getElementById("emailadminerr").innerHTML = " " + txt.bold();
          }
          //Testing for empty password is for Adding Records Only
          if (straction == 'add') {
            if (itpassword.trim()=="") {
              txt = " [required field]"
              document.getElementById("passwordadminErr").innerHTML = " " + txt.bold();
            }
          }
          if (txt == " [required field]") {
            return;
          }
          // Validate Email
          if (validateEmail (itemail) == "invalid") {
            var txt = "[Invalid Format]";
            document.getElementById("emailadminerr").innerHTML = " " + txt.bold();
            return;
          }
          //Validate Password
          if (itpassword !== "") {
            var strpw = itpassword;
            var strpwlen = strpw.length;
            if (strpwlen < 6) {
              swal({
                title: "Password atleast 6 characters!",
                button: "OK",
              });
              $('#itpassword').focus();
              $('#itpassword').select();
              return;
            }
          }

          $.ajax({
            url: 'crud.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
              if (response.match(/Admin Name.*/)) {
                swal({
                  title: response,
                  button: "OK",
                });
              } else {
                swal({
                  title: response,
                  icon: "success",
                  button: "OK",
                });
                
                if (straction == "update") {
                  // Updating the display record that has been edit
                  document.getElementById(itadminid + "-name").innerHTML = $('#itadminname').val();
                  document.getElementById(itadminid + "-email").innerHTML = $('#itemail').val();
                  if (cbactiveadmin == 1) {
                    document.getElementById(itadminid + "-active").innerHTML = "YES"
                  } else {
                    document.getElementById(itadminid + "-active").innerHTML = "NO"
                  }
                  //To remove the modal need to used close button of modal
                  $('#close-btn').click(); 
                } else { //add
                  //To remove the modal need to used close button of modal
                  $('#close-btn').click(); 
                  loadfile('admin');
                }
              }
            },
          });
        };
  
        // focust on admin name when modal pop up
        $('#modal-admin').on('shown.bs.modal', function () {
          $('#itadminname').focus();
        });
  
        // use one modal in page for add and update
        //add
        showCreateModal = function(){
          $('#modalheaderadmin').html('Adding admin'); //Changing the Modal Header Name
          $('#modal-admin').modal('toggle');
  
          //Remove the label and input in ID
          document.getElementById("lbladminid").style.display = "none";
          document.getElementById("itadminid").style.display = "none";
  
          //Remove the Content of Input, Select and others
          // clearvalue();
          //Initialized the value of elements when add record
          document.getElementById('itadminid').value = "";
          document.getElementById('itadminname').value = "";
          document.getElementById('itemail').value = "";
          document.getElementById('itpassword').value = "";
          $("#cbactiveadmin").prop("checked", true); //Default True When Adding Record
          
          document.getElementById("spanadminpassword").textContent = "";
        }
        //update
        showUpdateModal = function(strid){
          $('#modalheaderadmin').html('Updating admin'); //Changing the Modal Header Name
          $('#modal-admin').modal('toggle');
          
          //Remove the Content of Input, Select and others
          // clearvalue();
          //Initialized the value of elements when add record
          document.getElementById('itadminid').value = "";
          document.getElementById('itadminname').value = "";
          document.getElementById('itemail').value = "";
          document.getElementById('itpassword').value = "";
          $("#cbactiveadmin").prop("checked", true); //Default True When Adding Record
          
          //Show the label and input in ID
          document.getElementById("lbladminid").style.display = "block";
          document.getElementById("itadminid").style.display = "block";
          
          //Put the record from datables you edit to Modal Display
          document.getElementById('itadminid').value = strid;
          document.getElementById('itadminname').value = $('#'+ strid + "-name").html();
          document.getElementById('itemail').value = $('#'+ strid + "-email").html();
          if ($('#'+ strid + "-active").html() === "YES") {
            $("#cbactiveadmin").prop("checked", true);
          } else {
            $("#cbactiveadmin").prop("checked", false);
          }
          
          document.getElementById("spanadminpassword").textContent = "(Leave Blank to Retain the Existing)";
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
            
            fd.append('admin_delete','Yes') //for crud.php validation
            fd.append('adminid',strid);
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
                var t = $('#adminprint').DataTable();
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
    </script>