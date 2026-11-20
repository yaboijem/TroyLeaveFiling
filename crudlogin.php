<?php
    @ob_start(); if (!isset($_SESSION)) {session_start();}

    //Para sa verifytoken need ko magsetup na session for log_in_attempt = "Y" para di muna dumaan sa token test
    if(isset($_POST['user_login'])){
        // echo "sample";
        // die;
        $_SESSION['log_in_attempt'] = "Y";
    }

    include("dbconnect.php");
    
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // User Log In
        if(isset($_POST['user_login'])){
            //Gusto dito meron akong access na nakabuilt in yung user name and password para makapaglogin ako kahit walang akong record sa employeefiletbl,
            //ikaw nalang maglagay ng pangalan at id, at ang user type ay admin, para makapaglogin ako sa admin account at makapagsetup ng mga user accounts.
            
            // //Gusto ko dito meron akong access na nakabuilt in yung user name and password para makapaglogin ako kahit walang akong record sa employeefiletbl
            // //ang username ay "admin" at ang password ay "admin123", at ang user type ay admin, para makapaglogin ako sa admin account at makapagsetup ng mga user accounts.
            // if ($_POST['user_login_username'] === 'jiriellsicangco@gmail.com' && $_POST['user_login_password'] === 'Jiriel123!@#*') {
            //     echo "1"; //Valid
            //     $_SESSION['idno']=1015832026;
            //     $_SESSION['email']='sample@gmail.com';
            //     $_SESSION['deptcode']=0;
            //     $_SESSION['divcode']=0;
            //     $_SESSION['admin_or_user']='admin';
            //     $_SESSION['last_login_timestamp'] = time();

            //     // ✅ Generate a unique session token
            //     $session_token = bin2hex(random_bytes(32));                        
            //     // Save it in DB
            //     $update = $conn->prepare("UPDATE employeefiletbl SET session_token = ?, last_login = NOW() WHERE idno = ?");
            //     $update->bind_param("si", $session_token, $row['idno']);
            //     $update->execute();
            //     // Save in PHP session
            //     $_SESSION['session_token'] = $session_token;
            // } else {
                $user_login_username = mysqli_real_escape_string($conn, $_POST['user_login_username']);
                $user_login_password = $_POST['user_login_password']; //sha1(mysqli_real_escape_string($conn, $_POST['user_login_password']));
                
                //Check the username
                $stmt = $conn->prepare("SELECT employeefiletbl.*,departmentfiletbl.deptname from employeefiletbl 
                                        Left Outer Join departmentfiletbl on employeefiletbl.deptcode = departmentfiletbl.deptcode
                                        where email = ?");
                $stmt->bind_param("s", $user_login_username);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                if ($total_records > 0) {
                    $row = $result->fetch_assoc();
                    //Check Password
                    if ($row['active'] == 0) {
                        echo "2";
                    } else {
                        //if ($row['password']==$user_login_password) {
                        if (password_verify($user_login_password,$row['password'])) {
                            echo "1"; //Valid

                            $_SESSION['idno']=$row['idno'];
                            //$_SESSION['username']=$user_login_username;
                            $_SESSION['email']=$row['email'];
                            $_SESSION['deptcode']=$row['deptcode'];
                            $_SESSION['divcode']=$row['divcode'];
                            $_SESSION['admin_or_user']=$row['usertype'];
                            $_SESSION['last_login_timestamp'] = time();

                            // ✅ Generate a unique session token
                            $session_token = bin2hex(random_bytes(32));                        
                            // Save it in DB
                            $update = $conn->prepare("UPDATE employeefiletbl SET session_token = ?, last_login = NOW() WHERE idno = ?");
                            $update->bind_param("si", $session_token, $row['idno']);
                            $update->execute();
                            // Save in PHP session
                            $_SESSION['session_token'] = $session_token;

                        } else {
                            echo "0"; //Invalid
                        }
                    }
                } 
                else {
                    echo "0"; //Invalid
                }
                $stmt->close();
            // } //built-in admin check
        }

        // Admin Log In
        if(isset($_POST['admin_login'])){
            $admin_login_username = mysqli_real_escape_string($conn, $_POST['admin_login_username']);
            $admin_login_password = $_POST['admin_login_password']; //sha1(mysqli_real_escape_string($conn, $_POST['admin_login_password']));
            // echo $admin_login_username;
            // echo "-";
            // echo $admin_login_password;
            // exit;
            //Check the username
            $stmt = $conn->prepare("SELECT * from adminfiletbl where adminname = ?");
            $stmt->bind_param("s", $admin_login_username);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_records = $result->num_rows;
            $row = $result->fetch_assoc();
            if ($total_records > 0) {
                //Check Password
                if ($row['active'] == 0) {
                    echo "2";
                } else {
                    //if ($row['password']==$admin_login_password) {
                    if (password_verify($admin_login_password, $row['password'])) {
                        echo "1"; //Valid

                        $_SESSION['adminid']=$row['adminid'];
                        $_SESSION['adminname']=$admin_login_username;
                        $_SESSION['adminemail']=$row['email'];
                        $_SESSION['admin_or_user']='admin';
                        $_SESSION['last_login_timestamp'] = time();
                        $_SESSION['closedrequest'] = 1;
                        $_SESSION['system_type']='';
                        $_SESSION['shift']='';
                        $_SESSION['datelogin']='';
                        
                            
                        $sql = "SELECT utoken FROM timerforupdatetbl";
                        if($result = mysqli_query($conn, $sql)){
                            if(mysqli_num_rows($result) > 0){
                            $row = mysqli_fetch_array($result) ;
                                $_SESSION['utoken'] = $row['utoken'];
                            } else {
                                $_SESSION['utoken'] = ""; //Walang record
                            }
                        }

                    } else {
                        echo "0"; //Invalid
                    }
                }
            } else {
                echo "0";
            }
            $stmt->close();
        }
        
        if(isset($_POST['forgot_pw'])){
            if(isset($_POST['email'])){
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

                include('sendingemailused.php'); 
                
                $email = trim($_POST['email']);

                // Check if email exists in your employee table
                $stmt = $conn->prepare("SELECT idno, employeename FROM employeefiletbl WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows === 0){
                    echo "❌ Email not found in our system.";
                    exit;
                }

                $row = $result->fetch_assoc();
                $idno = $row['idno'];
                $name = $row['employeename'];

                // Generate token & expiration (10 mins)
                $token = bin2hex(random_bytes(16));
                date_default_timezone_set("asia/manila");
                //$date_time = new DateTime();
                $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                // Check for recent reset request
                $check = $conn->prepare("SELECT expires_at FROM passwordresetsfiletbl WHERE email = ?");
                $check->bind_param("s", $email);
                $check->execute();
                $checkResult = $check->get_result();

                if ($checkResult->num_rows > 0) {
                    $existing = $checkResult->fetch_assoc();
                    $expiresAt = strtotime($existing['expires_at']);
                    $now = time();

                    if ($expiresAt > $now - 600) { // 600 seconds = 10 minutes
                        echo "⏳ You've already requested a reset. Please wait before trying again.";
                        exit;
                    }
                }

                // Store reset request
                $conn->query("DELETE FROM passwordresetsfiletbl WHERE email='$email'"); // clear old requests
                $insert = $conn->prepare("INSERT INTO passwordresetsfiletbl (email, token, expires_at) VALUES (?,?,?)");
                $insert->bind_param("sss", $email, $token, $expires);
                $insert->execute();

                // Send email
                include('domainname.php');
                $resetLink = $domainName . "employeeresetpassword.php?token=$token&email=$email";

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $userNamePHPEmail;
                    $mail->Password   = $passwordPHPEmail; // Use Google App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom($userNamePHPEmail, "No Reply");
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = "Troy HRIS Password Reset Request";
                    $mail->Body    = "
                        <p>Dear $name,</p>
                        <p>We received a request to reset your password. Click the link below to reset it:</p>
                        <p><a href='$resetLink'><button style='background-color:#007dc5; color: white;'>Reset your password</button></a></p>
                        <p>This link is valid for <b>10 minutes</b>.</p>
                        <p>If you did not request this, please ignore.</p>
                    ";
                    $mail->send();
                    echo "✅ Reset link sent to $email. Please check your inbox.";
                } catch (Exception $e) {
                    echo "❌ Could not send email. Error: {$mail->ErrorInfo}";
                }
            }
        }

        if(isset($_POST['reset_pw'])){
            if(isset($_SESSION['resetpwemail'])){
                
                $token = $_SESSION['resetpwtoken'];
                $email = $_SESSION['resetpwemail'];
                $newPass = $_POST['newPass'];
                $confirmPass = $_POST['confirmPass'];
                
                // Validate token
                $stmt = $conn->prepare("SELECT * FROM passwordresetsfiletbl WHERE email=? AND token=? AND expires_at > NOW()");
                $stmt->bind_param("ss", $email, $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows === 0){
                    echo ("❌ Invalid or expired reset link.");
                    exit;
                }

                if (strlen($newPass) < 8) {
                    echo ("❌ Password must be at least 8 characters.");
                    exit;
                }

                if ($newPass !== $confirmPass) {
                    echo ("❌ Passwords do not match.");
                    return;
                }


                // $email = trim($_POST['email']);
                $newpass = password_hash($_POST['newPass'], PASSWORD_DEFAULT);
                //$email = $_POST['email'];

                // Update password
                $stmt = $conn->prepare("UPDATE employeefiletbl SET password=? WHERE email=?");
                $stmt->bind_param("ss", $newpass, $email);
                $stmt->execute();

                // Remove reset token
                $conn->query("DELETE FROM passwordresetsfiletbl WHERE email='$email'");
                
                unset($_SESSION['resetpwtoken']);
                unset($_SESSION['resetpwemail']);
                echo "✅ Password successfully updated. You may now <a href='index.php'>login</a>.";

            }
        }
    }
?>