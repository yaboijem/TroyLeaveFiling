 
<!-- Ends -->
    <style>
        /*#loading {*/
        /*  position: absolute;*/
        /*  top: 50%;*/
        /*  left: 50%;*/
        /*  width: 32px;*/
        /*  height: 32px;*/
          /* 1/2 of the height and width of the actual gif */
        /*  margin: -16px 0 0 -16px;*/
        /*  z-index: 100;*/
        /*  }*/
    </style>
    <!--<img id = "loading" src = "file/img/loading.gif" alt = "Loading indicator">-->
      
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header bg-primary">
          <h3 class="card-title">Department List</h3>
        </div>
          
        <div class="modal fade" id="modal-department" data-keyboard="true" data-backdrop="static">
          <div class="modal-dialog modal-dialog-centered" role="document"">
            <div class="modal-content">
              <div class="modal-header bg-primary">
                <h4 class="modal-title" id="modalHeaderdepartment"></h4>
                <button type="button" class="close" data-dismiss="modal"  aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <label id="lbldepartmentid">ID</label>
                <input type="text" class="form-control" id="itdepartmentid" value="" disabled>

                <label>Name</label>
                <span class="requiredfield" id="namedepartmentErr"><b></b></span>
                <input type="text" class="form-control" id="itdepartmentname" value="" required>
                
                <input type="checkbox" id="cbactivedepartment" name="activedepartmentfor" value="Active" checked>
                <label class="mt-1" for="activedepartmentfor"> Active </label><br>
              </div>
              <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
                <button type="button" onclick="clickbtndepartment()" class="btn btn-success" id="btndepartment">Submit</button>
              </div>
            </div>
            <!-- /.modal-content -->
          </div>
          <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <!-- /.card-header -->
        <div class="card-body">
          <!-- <a href='#' class = "btn btn-success" data-toggle="modal" data-target="#modal-department">Add New  <i class="fas fa-plus"></i></a> -->
          <!-- <input type='button' class = "btn btn-success" value='Show Create Modal' id='ShowModalButton' onclick='showCreateModal()' /> -->
          <button class="btn btn-success" type="button" onclick='showCreateModal()'>
            Add New  <i class="fas fa-plus"></i>
          </button>
          
          <div id="deptprintbar" style="float:right">
          </div> <br /> <br/>
          <table id="departmentprint" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Department Name</th>
                <th>Active</th>
                <th>Action</th>
                
              </tr>
            </thead>
            <tbody>
              <?php
                // Include config file
                require_once "dbconnect.php";
                // Attempt select query execution
                $sql = "SELECT * FROM departmentfiletbl order by deptid DESC";
                if($result = mysqli_query($conn, $sql)){
                  if(mysqli_num_rows($result) > 0){
                    $x = 0;
                    while($row = mysqli_fetch_array($result)){
                      ++$x;
                      echo '<tr id="'.$row['deptid'].'-rownumtr">
                        <td id="'.$row['deptid'].'-rownum">'.$x.'</td>
                        <td id="'.$row['deptid'].'-name">'.$row['deptname'].'</td>
                        <td id="'.$row['deptid'].'-active">';
                        if ($row['active'] == 1) {
                          echo 'YES';
                        } else {
                          echo 'NO';
                        }
                        echo '</td>
                        <td>
                            <a href="#" onclick="showUpdateModal(this.id)" id="'.$row['deptid'].'" class="mr-3" title="Update Record" data-toggle="tooltip"><span class="fas fa-pen-square fa-lg"></span></a>
                            <a href="#" onclick="validateDeleteRecord(this.id)" id="'.$row['deptid'].'" class="mr-3" title="Delete Record" data-toggle="tooltip"><span class="fas fa-trash fa-lg text-danger"></span></a>
                        </td>
                      </tr>';
                      $dept_numrows = $x;
                    }
                  }
                }
              ?>
            </tbody>
            <tfoot>
              <tr>
                <th>#</th>
                <th>Department Name</th>
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
  <!-- <script src="plugins/sweetalert2/sweetalert2.min.js"></script> -->
  <!-- <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script> -->
  <script src="plugins/sweetalert2/sweetalert.min.js"></script>
  <script src="plugins/bootstrap-pwtoggle/bootstrap.min.js"></script>
  <script src="plugins/bootstrap-pwtoggle/bootstrap-show-password.min.js"></script>

  <script>
          
      $(function() {
        // $("#oldrequestprint").DataTable({
        //     "responsive": true, "lengthChange": true, "autoWidth": false,"pageLength": 10,
        //     "buttons": ["copy", "csv", "excel", "pdf", "print"]
        // }).buttons().container().appendTo('#oldrequestprint_wrapper .col-md-6:eq(0)');
      
        var table = $('#departmentprint').DataTable({
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
        table.buttons().container().appendTo($('#deptprintbar'));
      });

      function clickbtndepartment() {
        // get the value of h4 modal header if updating or saving
        var str = $('#modalHeaderdepartment').html();
        var res = str.toUpperCase();
        if (res == "UPDATING DEPARTMENT") {
          straction = "update";
        } else {
          straction = "add";
        }
        // alert(straction);
        // return;
        
        var fd = new FormData();
        var itdepartmentid = $('#itdepartmentid').val(); 
        var itdepartmentname = $('#itdepartmentname').val();
        
        // alert ($('#itdepartmentid').val());
        // return;

        var cbactivedepartment = $("input#cbactivedepartment");
        if (cbactivedepartment.is(":checked")) {
          cbactivedepartment = 1;
          // alert(cbactivedepartment);
        } else {
          // alert("not sending data - box is not checked");
          cbactivedepartment = 0;
        }
        
        fd.append('department_action',straction) //for crud.php validation
        fd.append('itdepartmentid',itdepartmentid);
        fd.append('itdepartmentname',itdepartmentname);
        fd.append('cbactivedepartment',cbactivedepartment);

        var txt = "";
        document.getElementById("namedepartmentErr").innerHTML = "";
        
        if (itdepartmentname.trim()=="") {
          txt = " [required field]"
          document.getElementById("namedepartmentErr").innerHTML = " " + txt.bold();
        } 

        if (txt == " [required field]") {
          return;
        }

        $.ajax({
          url: 'crud.php',
          type: 'post',
          data: fd,
          contentType: false,
          processData: false,
          success: function(response){
            if (response.match(/Department Name.*/)) {
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
                // Updating to prevent reloading of page I used to add the value in elements
                document.getElementById(itdepartmentid + "-name").innerHTML = $('#itdepartmentname').val();
                if (cbactivedepartment == 1) {
                  // alert(cbactivedepartment);
                  document.getElementById(itdepartmentid + "-active").innerHTML = "YES"
                } else {
                  // alert(cbactivedepartment);
                  document.getElementById(itdepartmentid + "-active").innerHTML = "NO"
                }
                
                $('#close-btn').click(); //To remove the modal need to used close button of modal
              } else { //add
                $('#close-btn').click(); //To remove the modal need to used close button of modal
                loadfile('department');
                
                // //Get the last row of records
                // js_dept_numrows = <?php //echo $dept_numrows; ?>;
                // // alert(js_dept_numrows);

                // var table = document.getElementById("departmentprint");
                // var row = table.insertRow(0);
                // var cell1 = row.insertCell(0);
                // var cell2 = row.insertCell(1);
                // cell1.innerHTML = "NEW CELL1";
                // cell2.innerHTML = "NEW CELL2";
              }
            }
          },
        });
      };

      // focust on department name when modal pop up
      $('#modal-department').on('shown.bs.modal', function () {
        $('#itdepartmentname').focus();
      });

      // use one modal in page for add and update
      //add
      showCreateModal = function(){
        $('#modalHeaderdepartment').html('Adding Department'); //Changing the Modal Header Name
        $('#modal-department').modal('toggle');

        //Remove the label and input in ID
        document.getElementById("lbldepartmentid").style.display = "none";
        document.getElementById("itdepartmentid").style.display = "none";

        //Initialized the value of elements when add record
        document.getElementById('itdepartmentid').value = "";
        document.getElementById('itdepartmentname').value = "";
        $("#cbactivedepartment").prop("checked", true); //Default True When Adding Record
      }
      //update
      showUpdateModal = function(strid){
        $('#modalHeaderdepartment').html('Updating Department'); //Changing the Modal Header Name
        $('#modal-department').modal('toggle');

        //Initialized the value of elements when add record
        document.getElementById('itdepartmentid').value = "";
        document.getElementById('itdepartmentname').value = "";
        $("#cbactivedepartment").prop("checked", true); //Default True When Adding Record

        // alert($('#'+ strid + "-name").html());
        // $('#itdepartmentname').val() = $('#'+ strid + "-name").html()
        document.getElementById("lbldepartmentid").style.display = "block";
        document.getElementById("itdepartmentid").style.display = "block";
        
        // document.getElementById("lbldepartmentid").style.visibility = "visible";
        // document.getElementById("itdepartmentid").style.visibility = "visible";
        
        document.getElementById('itdepartmentid').value = strid;
        document.getElementById('itdepartmentname').value = $('#'+ strid + "-name").html();
        if ($('#'+ strid + "-active").html() === "YES") {
          $("#cbactivedepartment").prop("checked", true);
        } else {
          $("#cbactivedepartment").prop("checked", false);
        }
      }
      
      function validateDeleteRecord(strid) {
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
          
          fd.append('department_delete','Yes') //for crud.php validation
          fd.append('departmentid',strid);
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
              var t = $('#departmentprint').DataTable();
              t.row("#"+strid+'-rownumtr').remove().draw();
            },
          });  
        } 
        });
      }
  </script>
    