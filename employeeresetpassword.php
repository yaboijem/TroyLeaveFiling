<?php 
  if (!isset($_SESSION)) {session_start();} 

  if (isset($_SESSION['adminname']) || isset($_SESSION['admin_or_user'])) { 
    echo "<script>window.open('dashboard.php','_self')</script>";
  }

  if(isset($_GET['token']) && isset($_GET['email'])){
      $token = $_GET['token'];
      $email = $_GET['email'];
      $_SESSION['resetpwtoken'] = $token;
      $_SESSION['resetpwemail'] = $email;

      include("dbconnect.php");
      // Validate token
      $stmt = $conn->prepare("SELECT * FROM passwordresetsfiletbl WHERE email=? AND token=? AND expires_at > NOW()");
      $stmt->bind_param("ss", $email, $token);
      $stmt->execute();
      $result = $stmt->get_result();

      if($result->num_rows === 0){
          die("❌ Invalid or expired reset link.");
      }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password</title>
  <link rel="shortcut icon" type="images/x-icon" href="dist/img/Logo.png">
  <link rel="icon" href="assets/images/logo.png">

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    * { box-sizing: border-box; font-family: Arial, sans-serif; }
    body, html { margin: 0; padding: 0; height: 100%; }
    body {
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .reset-container { 
      background-color: white;
      padding: 40px 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    .reset-container .input-group {
      position: relative;
      width: 100%;
      margin-bottom: 20px;
    }
    .reset-container input[type="password"], 
    .reset-container input[type="text"] { 
      width: 100%;
      padding: 12px 40px 12px 12px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .toggle-eye {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 18px;
      color: #555;
    }
    .reset-container button { 
      width: 100%;
      padding: 12px;
      background-color: #007dc5;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    .reset-container .login-link { 
      margin-top: 15px;
      font-size: 14px;
    }
    .reset-container .login-link a { 
      color: #007dc5;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <h2 style="margin-bottom: 20px;">Reset Password</h2>
    
    <!-- Message box -->
    <div id="messageBox" style="display:none; margin-bottom:15px; padding:10px; border-radius:5px; font-size:14px;"></div>

    <form id="resetForm">
      <div class="input-group">
        <input type="password" id="newPassword" placeholder="New Password" required>
        <i class="fa-solid fa-eye-slash toggle-eye" onclick="togglePassword('newPassword', this)"></i>
      </div>
      <div class="input-group">
        <input type="password" id="confirmPassword" placeholder="Confirm Password" required>
        <i class="fa-solid fa-eye-slash toggle-eye" onclick="togglePassword('confirmPassword', this)"></i>
      </div>
      <button type="submit">Update Password</button>
    </form>

    <div class="login-link">
      <a href="index.php">Back to Login</a>
    </div>
  </div>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Show message helper
    function showMessage(msg, type){
        let box = document.getElementById("messageBox");
        box.style.display = "block";
        box.style.backgroundColor = (type === "error") ? "#f8d7da" : "#d4edda";
        box.style.color = (type === "error") ? "#721c24" : "#155724";
        box.style.border = (type === "error") ? "1px solid #f5c6cb" : "1px solid #c3e6cb";
        box.innerHTML = msg;
    }

    function togglePassword(fieldId, icon) {
      let field = document.getElementById(fieldId);
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      } else {
        field.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      }
    }

    // Form submit
    document.getElementById("resetForm").addEventListener("submit", function(e){
        e.preventDefault();
        
        let newPass = document.getElementById("newPassword").value.trim();
        let confirmPass = document.getElementById("confirmPassword").value.trim();

        if (newPass.length < 8) {
            showMessage("❌ Password must be at least 8 characters.", "error");
            return;
        }
        if (newPass !== confirmPass) {
            showMessage("❌ Passwords do not match.", "error");
            return;
        }

        var fd = new FormData();
        fd.append('reset_pw', 'Yes');
        fd.append('newPass', newPass);
        fd.append('confirmPass', confirmPass);

        $.ajax({
            url: "crudlogin.php",
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            dataType: "text",
            success: function(response) {
                if (response.trim() === "") {
                    showMessage("⚠️ No response from server.", "error");
                } else {
                    showMessage(response, "success");
                }
            },
            error: function() {
                showMessage("⚠️ Error sending request.", "error");
            }
        });
    });
</script>
