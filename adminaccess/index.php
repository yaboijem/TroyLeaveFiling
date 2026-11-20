<?php 
  if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    // echo $_SESSION['adminname'];
    // echo $_SESSION['username'];
  if (isset($_SESSION['adminname']) || isset($_SESSION['username'])) { //To check if the admin is log in already it will go back to dashboard.php
    echo "<script>window.open('../dashboard.php','_self')</script>";
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ADMIN ORAS - Log In</title>
    <link rel="icon" href="../dist/img/Logo.png">
    
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome CSS-->
    <link rel="stylesheet" href="vendor/font-awesome/css/font-awesome.min.css">
    <!-- Fontastic Custom icon font-->
    <link rel="stylesheet" href="css/fontastic.css">
    <!-- Google fonts - Poppins -->
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,700"> -->
    <!-- theme stylesheet-->
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="css/custom.css">
  </head>
  <body onload="form_load()">
    <div class="page login-page">
      <div class="container d-flex align-items-center">
        <div class="form-holder has-shadow">
          <div class="row">
            <!-- Logo & Information Panel-->
            <div class="col-lg-6">
              <div class="info d-flex align-items-center" style="background-color:#146fd6">
                <div class="content">
                  <div class="logo">
                    <h1>Online Request Approval System</h1>
                    <!-- <h1>QR Code Ticketing</h1> -->
                  </div>
                  <!-- <p>QR Code enabled ticket generator</p> -->
                  <p>Blockchain powered IOT based document tracking system</p>
                </div>
              </div>
            </div>
            <!-- Form Panel    -->
            <div class="col-lg-6 bg-white">
              <div class="form d-flex align-items-center">
                <div class="content">
                    <div class="d-flex justify-content-center align-items-center" style="font-size: 20px; color:#aaa">Admin Log In</div>
                    <div class="form-group">
                      <input id="admin_login_username" type="text" name="username" required data-msg="Please enter your username" class="input-material">
                      <label id="lbl_admin_login_username" for="admin_login_username" class="label-material">User Name</label>
                    </div>
                    <div class="form-group">
                      <input id="admin_login_password" type="password" name="password" required data-msg="Please enter your password" class="input-material">
                      <label id="lbl_admin_login_password" for="admin_login_password" class="label-material">Password</label>
                      <input type="checkbox" onclick="showpw()" class="mt-2"> <span style="color:#aaa;"> Show Password</span>

                    </div><button id="admin-login" onclick="submit_admin_login()" class="btn btn-primary">Login</button>
                  <!--a href="#" class="forgot-pass">Forgot Password?</a><br><small>Do not have an account? </small><a href="register.html" class="signup">Signup</a-->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="copyrights text-center">
        <p>Design by <a href="https://bootstrapious.com/p/admin-template" class="external">Bootstrapious</a>
          <!-- Please do not remove the backlink to us unless you support further theme's development at https://bootstrapious.com/donate. It is part of the license conditions. Thank you for understanding :)-->
        </p>
      </div>
    </div>
    <!-- JavaScript files-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/popper.js/umd/popper.min.js"> </script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/jquery.cookie/jquery.cookie.js"> </script>
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
    <!-- Main File-->
    <script src="js/front.js"></script>
    <script src="../plugins/sweetalert2/sweetalert.min.js"></script>

    <script>
      function showpw() {
        var x = document.getElementById("admin_login_password");
        if (x.type === "password") {
          x.type = "text";
        } else {
          x.type = "password";
        }
      }

      function submit_admin_login() {
        var admin_login_username = $('#admin_login_username').val();
        var admin_login_password = $('#admin_login_password').val();
        if (admin_login_username) { //if there is value on username
          //Bring back the color
          document.getElementById("lbl_admin_login_username").style.color = "#aaa";
        } else {
          //Color it red means need to fill up
          document.getElementById("lbl_admin_login_username").style.color = "red";
          document.getElementById("lbl_admin_login_username").focus();
          return;
        }
        if (admin_login_password) { //if there is value on password
          //Bring back the color
          document.getElementById("lbl_admin_login_password").style.color = "#aaa";
        } else {
          //Color it red means need to fill up
          document.getElementById("lbl_admin_login_password").style.color = "red";
          document.getElementById("lbl_admin_login_password").focus();
          return;
        }
        
        var fd = new FormData();
        fd.append('admin_login','accesspass'); //for crud.php validation
        fd.append('admin_login_username',admin_login_username);
        fd.append('admin_login_password',admin_login_password);

        $.ajax({  
          url: '../crud.php',
          type: 'post',
          data: fd,
          contentType: false,
          processData: false,
          success: function(response){
            if ($.trim(response) == "0") {
              swal({
                title: "Invalid username or password?",
                text: "",
                icon: "warning",
                buttons: 
                  'OK' ,
                dangerMode: true,
              });
            } else if ($.trim(response) == "1") {
              window.open('../dashboard.php','_self')
            } else if ($.trim(response) == "2") {
              swal({
                title: "Username is Inactive!",
                text: "",
                icon: "warning",
                buttons:  
                  'OK' ,
                dangerMode: true,
              });
            } else {
              alert (response); 
            }; 
          }
        });
      }  
      
      // Use for keypress enter in password to call the log in button
      var input = document.getElementById("admin_login_password");
      input.addEventListener("keyup", function(event) {
        if (event.keyCode === 13) {
          event.preventDefault();
          document.getElementById("admin-login").click();
        }
      });

      function form_load() {
        setTimeout(function (){
            $('#admin_login_username').focus();
        }, 1000);
      }
    </script>
  </body>
</html>