    <?php
        // @ob_start(); if (!isset($_SESSION)) {session_start();}
        // echo $loginname;
    ?>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="modal fade" id="modal-user" data-keyboard="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                <div class="modal-header bg-primary">
                  <h4 class="modal-title" id="modalHeaderUser"></h4>
                  <button type="button" class="close" data-dismiss="modal"  aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">

                    <form>
                      <div class="form-group">
                        <label id="lbl_user_name" for="lbl_user_name" class="label-material">User Name</label>
                        <input type="text" id="user_name" class="form-control" disabled>
                      </div>
                      <div class="form-group">
                        <label id="lbl_user_shift" for="lbl_user_name" class="label-material">User Shift</label>
                        <input type="text" id="usershift" class="form-control" disabled>
                      </div>
                      <div class="form-group">
                        <label id="lbl_datelogin" for="lbl_user_name" class="label-material">Date Log In</label>
                        <input type="text" id="datelogin" class="form-control" disabled>
                      </div>
                    </form>
                  

                </div>
                <div class="modal-footer justify-content-between">
                  <button type="button" onclick="window.location.reload();" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Cancel</button>
                  <button type="button" onclick="clickbtnusercp()" class="btn btn-success" id="ok-btn">Ok</button>
                </div>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
          </div>
          <!-- /.modal -->
  
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    
    <!-- DataTables  & Plugins -->
    <script src="plugins/sweetalert2/sweetalert.min.js"></script>
    <!-- Password Toggle -->
    <!-- <script src="plugins/bootstrap-pwtoggle/jquery.min.js"></script> -->
    <script src="plugins/bootstrap-pwtoggle/bootstrap.min.js"></script>
    <script src="plugins/bootstrap-pwtoggle/bootstrap-show-password.min.js"></script>
  
    <script>
        function clickbtnusercp() {
          var fd = new FormData();
          fd.append('close_shift','1') //for crud.php validation
          fd.append('systemtype','fo');
          
          $.ajax({
            url: 'crud.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
              swal({
                  title: response,
                  icon: "success",
                  button: "OK",
              });
              window.location.reload();
            },
          });
        };
        
        // // Use for keypress enter in password to call the log in button
        // var input = document.getElementById("itpassword");
        // input.addEventListener("keyup", function(event) {
        //     if (event.keyCode === 13) {
        //     event.preventDefault();
        //     document.getElementById("ok-btn").click();
        //     }
        // });

        $('#modalHeaderUser').html('Do you want to close shift front office?'); //Changing the Modal Header Name
        $('#modal-user').modal('toggle');
        
        var fd = new FormData();
        fd.append('system_type','fo');
        fd.append('validate_user_log','1') //for crud.php validation
        
        $.ajax({
        url: 'crud.php',
        type: 'post',
        data: fd,
        contentType: false,
        processData: false,
        success: function(response){
            if (response != "0") {
              console.log(response);

              var parsed = JSON.parse(response); // an *array* that contains the user
              var data = parsed[0];         // a simple user
              document.getElementById("user_name").value = data.username;
              let text = data.shift; 
              document.getElementById("usershift").value = text.replace("s", "SHIFT ");
              document.getElementById("datelogin").value = data.datelogin;
            } else {
              $("#ok-btn").prop('disabled', true)
              document.getElementById("user_name").value = "";
              document.getElementById("usershift").value = "";
              document.getElementById("datelogin").value = "";
            }
        },
        });
    </script>