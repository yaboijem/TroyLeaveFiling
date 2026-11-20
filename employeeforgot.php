<?php 
  // if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
  //   if (isset($_SESSION['email'])) {
  //     header("Location: index.php");
  //     exit;
  //   }
  if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
  
  unset($_SESSION['log_in_attempt']); //Unset ko ito para kapag dumaan sa dbconnect.php ay hindi na siya mag-include ng verifytoken.php

  if (isset($_SESSION['adminname']) || isset($_SESSION['admin_or_user']))  { //To check if the admin is log in already it will go back to dashboard.php
    echo "<script>window.open('dashboard.php','_self')</script>";
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password</title>
  <link rel="shortcut icon" type="images/x-icon" href="dist/img/Logo.png">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <title>Forgot Password</title>
  <link rel="icon" href="assets/images/logo.png">
  <!-- <link href="style.css" rel="stylesheet"> -->
  <style>
    * {
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
    }
    body {
      /* background: url('assets/images/mayon.jpg') no-repeat center center fixed; */
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .forgot-container { 
      background-color: white;
      padding: 40px 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      text-align: center;
      height: 90%; /* Adjusted height */
    }
    .forgot-container img { 
      width: 140px;
      margin-bottom: 20px;
    }
    .forgot-container input[type="email"] { 
      width: 100%;
      padding: 12px;
      margin: 10px 0 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .forgot-container button { 
      width: 100%;
      padding: 12px;
      background-color: #007dc5;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    .forgot-container.signup-link { 
      margin-top: 20px;
      font-size: 14px;
    }
    .forgot-container .signup-link a { 
      color: #007dc5;
      text-decoration: none;
    }
    .forgot-container .login-link { 
      margin-top: 10px;
      font-size: 14px;
    }
    .forgot-container .login-link a { 
      color: #007dc5;
      text-decoration: none;
    }

    #modalLoadingOverlay {
      display: none !important;
      position: fixed;
      inset: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      background: rgba(255,255,255,.6);
      z-index: 2000;
    }

    #modalLoadingOverlay.show {
      display: flex !important;
    }
  </style>
</head>
<body>
  <div class="forgot-container">
    <div id="modalLoadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
        style="background-color: rgba(255,255,255,0.6); z-index: 2000;">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="sr-only">Loading...</span>
      </div>
    </div>

    <!-- <a href="index.php"><img src="assets/images/logo.png" alt="Reset password Logo"></a> -->
    <h2 style="margin-bottom: 20px;">Reset password</h2>
    <p style="margin-bottom: 20px;">Enter your email address to receive a password reset link.</p>
    <!-- Message box -->
    <div id="messageBox" style="display:none; margin-bottom:15px; padding:10px; border-radius:5px; font-size:14px;">dffgd</div>

    <form id="forgotForm">
      <input type="email" id="email" placeholder="Email Address" autocomplete="email" required>
      <button type="submit">Send Reset Email</button>
    </form>
    <div class="login-link">
      <a href="index.php">Back to Login</a>
    </div>
    <!-- <div class="signup-link">
      Don't have an account? <a href="signup.php">Sign up</a>
    </div> -->
  </div>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="plugins/sweetalert2/sweetalert.min.js"></script>
<script>
    function showModalLoading() {
      document.getElementById("modalLoadingOverlay").classList.add("show");
    }

    function hideModalLoading() {
      document.getElementById("modalLoadingOverlay").classList.remove("show");
    }

    // Helper to display message
    function showMessage(msg, type){
        let box = document.getElementById("messageBox");
        box.style.display = "block";
        box.style.backgroundColor = (type === "error") ? "#f8d7da" : "#d4edda";
        box.style.color = (type === "error") ? "#721c24" : "#155724";
        box.style.border = (type === "error") ? "1px solid #f5c6cb" : "1px solid #c3e6cb";
        box.innerHTML = msg;
    }

    document.getElementById("forgotForm").addEventListener("submit", function(e){
        e.preventDefault();
        let email = document.getElementById("email").value.trim();

        // Email format validation
        let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!regex.test(email)){
            swal("❌ Invalid Email", "Please enter a valid email address.", "error");
            return;
        }

        // Ask confirmation first
        swal({
            title: "Reset Password?",
            text: "Do you want to reset your password?",
            icon: "warning",
            buttons: ["No", "Yes"],  // No = cancel, Yes = confirm
            dangerMode: true,
        }).then((willReset) => {
            if (willReset) {
                // Proceed with AJAX only if Yes
                var fd = new FormData();
                fd.append('forgot_pw', 'Yes'); // for crudlogin.php validation
                fd.append('email', email);

                $.ajax({
                    url: "crudlogin.php",
                    type: "POST",
                    data: fd,
                    processData: false,
                    contentType: false,
                    dataType: "text",
                    beforeSend: function() {
                      showModalLoading();
                    },
                    success: function(response) {
                        if (response.trim() === "") {
                            swal("⚠️ No response from server.", {
                                icon: "warning"
                            });
                        } else {
                            if (response.match(/❌ Email not found.*/)) {
                               swal("", response, "info"); 
                            } if (response.match(/⏳ You've already requested.*/)) {
                               swal("", response, "info");
                            } else if (response.match(/✅ Reset link.*/)) {
                               swal("✅ Success", response, "success");
                            }else {
                              swal("Server Response", response, "info");
                            }

                        }
                    },
                    error: function(xhr, status, error) {
                        swal("❌ Error", "⚠️ Error sending request.", "error");
                    },
                      complete: function() {
                        hideModalLoading();
                    }
                });
            } else {
                // Cancel pressed
                swal("❌ Cancelled", "Password reset request was cancelled.", "info");
            }
        });
    });

</script>