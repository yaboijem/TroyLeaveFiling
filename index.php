<?php 
  if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set

  $returnTo = '';
  if (isset($_GET['return']) && is_string($_GET['return'])) {
    $candidate = $_GET['return'];
    $decoded = rawurldecode($candidate);
    $parts = parse_url($candidate);
    $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';
    $portalPath = $path === '/TroyLeaveFiling/portal' || str_starts_with($path, '/TroyLeaveFiling/portal/');
    if (is_array($parts) && !isset($parts['scheme']) && !isset($parts['host']) && $portalPath
        && !str_contains($candidate, '//') && !str_contains($candidate, '\\') && !str_contains($decoded, '..')
        && !str_contains($decoded, '//') && !str_contains($decoded, '\\')
        && !preg_match('/[<>"\']/', $candidate) && !preg_match('/%2e|%2f|%5c/i', $candidate)) {
      $returnTo = $candidate;
    }
  }

  if (isset($_SESSION['adminname']) || isset($_SESSION['admin_or_user']))  { //To check if the admin is log in already it will go back to dashboard.php
    $target = $returnTo !== '' ? $returnTo : 'dashboard.php';
    echo "<script>window.open(" . json_encode($target, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ",'_self')</script>";
    exit;
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>USER RMS - Log In</title>
    <link rel="icon" href="dist/img/Logo.png">
    
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="adminaccess/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome CSS-->
    <link rel="stylesheet" href="adminaccess/vendor/font-awesome/css/font-awesome.min.css">
    <!-- Fontastic Custom icon font-->
    <link rel="stylesheet" href="adminaccess/css/fontastic.css">
    <!-- theme stylesheet-->
    <link rel="stylesheet" href="adminaccess/css/style.default.css" id="theme-stylesheet">
    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="adminaccess/css/custom.css">
  </head>
  <body onload="form_load()">
    <div class="page login-page">
      <div class="container d-flex align-items-center">
        <div class="form-holder">
          <div class="row">
            <!-- Logo & Information Panel-->
            <div class="col-lg-6">
              <div class="info d-flex align-items-center" style="background-color:#004080">
                <div class="content">
                  <div class="logo">
                    
                    <!-- <h1>QR Code Ticketing</h1> -->
                  </div>
                  <!-- <p>QR Code enabled ticket generator</p> -->
                  <h1 style="position: relative;
    display: block;
    font-size: 2.5em;
    color:#ffffff;
    line-height: .9em;
    text-shadow: -1px 1px 1px rgba(0,0,0,.4), -1px -1px 0 #00ff00;text-align: center;">L-Soft Management System</h1>
                </div>
              </div>
            </div>
            <!-- Form Panel    -->
            <div class="col-lg-6 bg-white">
              <div class="form d-flex align-items-center">
                <div class="content">
                    <div class="d-flex justify-content-center align-items-center" style="font-size: 20px; color:#aaa">Employee Log In</div>
                    <!-- <div class="form-group">
                        <span style="color:red" id="system_type_Err"><b></b></span>
                        <select onchange="findsystemtype(this.id)" name="system_type" id="system_type" class="form-control" style="color:#aaa">
                          <option value="hk">Housekeeping</option>
                        </select>
                    </div> -->
                    <div class="form-group">
                      <input id="user_login_username" type="email" name="username" required data-msg="Please enter your email" class="input-material" required>
                      <label id="lbl_user_login_username" for="user_login_username" class="label-material">Email</label>
                    </div>
                    <div class="form-group">
                      <input id="user_login_password" type="password" name="password" required data-msg="Please enter your password" class="input-material">
                      <label id="lbl_user_login_password" for="user_login_password" class="label-material">Password</label>
                      <input type="checkbox" onclick="showpw()" class="mt-2"> <span style="color:#aaa;"> Show Password</span>
                    </div>
                    <!-- <div class="form-group">
                        <span style="color:red" id="user_shift_Err"><b></b></span>
                        <select name="user_shift" id="user_shift" class="form-control" style="color:#aaa">
                          <option style="color:red !important" value="" selected disabled hidden>Select Shift...</option>
                          <option value="s1">Shift 1 (6AM TO 2PM)</option>
                          <option value="s2">Shift 2 (2PM TO 10PM)</option>
                          <option value="s3">Shift 3 (10PM TO 6PM)</option>
                        </select>
                    </div> -->
                    <!-- <div class="form-group">
                      <span style="color:red" id="user_datelogin"><b></b></span>
                      <input id="user_login_datelogin" type="date" name="datelogin" class="input-material" style="color:#aaa">
                      <label id="lbl_user_login_datelogin" for="user_login_datelogin" class="label-material">Date Log In</label>
                    </div> -->
                    <div class="form-group d-flex justify-content-between align-items-center">
                      <button id="user-login" onclick="submit_user_login()" class="btn btn-primary">Login</button>
                      <a href="employeeforgot.php" class="forgot-pass" style="margin-left: 10px; color: #007bff; text-decoration: none;">Forgot Password?</a>
                    </div>

                    <!-- <button id="user-login" onclick="submit_user_login()" class="btn btn-primary">Login</button> -->
                  <!--a href="#" class="forgot-pass">Forgot Password?</a><br><small>Do not have an account? </small><a href="register.html" class="signup">Signup</a-->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- JavaScript files-->
    <script src="adminaccess/vendor/jquery/jquery.min.js"></script>
    <script src="adminaccess/vendor/popper.js/umd/popper.min.js"> </script>
    <script src="adminaccess/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="adminaccess/vendor/jquery.cookie/jquery.cookie.js"> </script>
    <script src="adminaccess/vendor/chart.js/Chart.min.js"></script>
    <script src="adminaccess/vendor/jquery-validation/jquery.validate.min.js"></script>
    <!-- Main File-->
    <script src="adminaccess/js/front.js"></script>
    <script src="plugins/sweetalert2/sweetalert.min.js"></script>

    <script>
      var returnTo = <?php echo json_encode($returnTo, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      function showpw() {
        var x = document.getElementById("user_login_password");
        if (x.type === "password") {
          x.type = "text";
        } else {
          x.type = "password";
        }
      }

      function submit_user_login() {
        //Kung meron ng log in ang system type
        if(document.getElementById('user_login_username').disabled == true){
          loginexist = 'YES';
        } else {
          loginexist = 'NO';
        }

        var user_login_username = $('#user_login_username').val();
        var user_login_password = $('#user_login_password').val();
        // var datelogin = $('#user_login_datelogin').val();
        // Validate Email
        if (validateEmail (user_login_username) == "invalid") {
          swal({
            title: "Invalid Email Format?",
            text: "",
            icon: "warning",
            buttons: 
              'OK' ,
            dangerMode: true,
          });
          return;
        }

        // system_type = optionsystem_type.value
        // user_shift = option.value;
        if (user_login_username) { //if there is value on username
          //Bring back the color
          document.getElementById("lbl_user_login_username").style.color = "#aaa";
        } else {
          //Color it red means need to fill up
          document.getElementById("lbl_user_login_username").style.color = "red";
          document.getElementById("lbl_user_login_username").focus();
          return;
        }
        if (user_login_password) { //if there is value on password
          //Bring back the color
          document.getElementById("lbl_user_login_password").style.color = "#aaa";
        } else {
          //Color it red means need to fill up
          document.getElementById("lbl_user_login_password").style.color = "red";
          document.getElementById("lbl_user_login_password").focus();
          return;
        }
        

        // alert(system_type);
        var fd = new FormData();
        fd.append('user_login','accesspass'); //for crud.php validation
        fd.append('user_login_username',user_login_username);
        fd.append('user_login_password',user_login_password);
        // fd.append('system_type',system_type)
        // fd.append('user_shift',user_shift);
        // fd.append('datelogin',datelogin);
        fd.append('loginexist',loginexist);
        $.ajax({  
          url: 'crudlogin.php',
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
              window.open(returnTo || 'dashboard.php','_self')
            } else if ($.trim(response) == "2") {
              swal({
                title: "Username is Inactive!",
                text: "",
                icon: "warning",
                buttons:  
                  'OK' ,
                dangerMode: true,
              });
            } else if ($.trim(response) == "3") {
              swal({
                title: system_typename + ", " + shiftname + " and date log in " + datelogin + " already used!",
                text: "",
                icon: "warning",
                buttons:  
                  'OK' ,
                dangerMode: true,
              });
            } else {
              alert (response); 
              console.log(response);
            }; 
          }
        });
      }  
      
      // Use for keypress enter in password to call the log in button
      var input = document.getElementById("user_login_password");
      input.addEventListener("keyup", function(event) {
        if (event.keyCode === 13) {
          event.preventDefault();
          document.getElementById("user-login").click();
        }
      });

      function form_load() {
        setTimeout(function (){
            $('#system_type').focus();
        }, 1000);
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
  </body>
</html>