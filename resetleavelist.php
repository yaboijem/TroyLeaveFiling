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
                      <input type="password" id="itpassword" class="form-control" maxlength="1024" data-toggle="password" placeholder="" required>
                    </div>
                  </form>
                </div>
                <div class="modal-footer justify-content-between">
                  <button type="button" onclick="window.location.reload();" class="btn btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
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
          // get the value of h4 modal header if updating or saving
          var str = $('#modalHeaderUser').html();
          var res = str.toUpperCase();
          if (res == "PLEASE ENTER THE OLD PASSWORD") {
            straction = "validate";
          } else {
            straction = "update";
          }

          //Put in variable the value of input including the validation of department(select elements) and checkbox to assign value if check or uncheck
        //   var ituserid = <?php //echo $_SESSION['username']; ?> 
          var itpassword = $('#itpassword').val(); 
          
          var fd = new FormData();
          fd.append('user_change_password',straction) //for crud.php validation
        //   fd.append('ituserid',ituserid);
          fd.append('itpassword',itpassword);
          
          //Validate Password
          if (itpassword == "") {
              $('#itpassword').focus();
              $('#itpassword').select();
              return;
          }

          // Validate Password Upon Updating
          if (straction === "update") {
            if (itpassword !== "") {
              const strpw = itpassword;
              const strpwlen = strpw.length;

              // Regular expression for strong password
              const strongPwRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

              if (!strongPwRegex.test(strpw)) {
                swal({
                  title: "Weak Password!",
                  text: "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.",
                  button: "OK",
                });

                setTimeout(function () {
                  $('#itpassword').focus();
                }, 1000);

                return;
              }
            }
          }

          $.ajax({
            url: 'crud.php',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
              // alert(response);
              if (response == 0) {
                swal({
                  title: "Wrong Old Password",
                  button: "OK",
                });

                setTimeout(function (){
                    $('#itpassword').focus();
                }, 1000);
                return;
              } else {
                if (straction == "validate") {
                    $('#modalHeaderUser').html('Please enter the New Password'); //Changing the Modal Header Name
                    //bring back to dot letter of password if ever you change it with visible letter (When you type the old passoword with visible letter when new password to type it will appear the old password)
                    // alert($('.glyphicon').attr('class'));
                    var itpasswordclass = $('.glyphicon').attr('class');
                    if (itpasswordclass.trim() == 'glyphicon icon-eye-close glyphicon-eye-close') {
                      //$(".glyphicon").addClass("icon-eye-open glyphicon-eye-open").removeClass("icon-eye-close glyphicon-eye-close");
                      $(".glyphicon").click();
                    }
                    
                    // focust on User name when modal pop up setTimeout the time to "fade in" the modal doesn’t let you set focus on input. You need to create a delay
                    setTimeout(function (){
                        $('#itpassword').focus();
                    }, 1000);
                    document.getElementById('itpassword').value = "";
                    document.getElementById('itpassword').placeholder = "At least 8 characters";
                    return;
                } else {
                    swal("Password Changed Successfully",{icon: "success"})
                    .then((value) => {
                        window.location.reload();
                    });
                    
                    return;
                }
              }
            },
          });
        };
        
        // Use for keypress enter in password to call the log in button
        var input = document.getElementById("itpassword");
        input.addEventListener("keyup", function(event) {
            if (event.keyCode === 13) {
            event.preventDefault();
            document.getElementById("ok-btn").click();
            }
        });

        $('#modalHeaderUser').html('Please enter the password to reset the leave balance'); //Changing the Modal Header Name
        $('#modal-user').modal('toggle');
        // focust on User name when modal pop up setTimeout the time to "fade in" the modal doesn’t let you set focus on input. You need to create a delay
        setTimeout(function (){
            $('#itpassword').focus();
        }, 1000);
    </script>