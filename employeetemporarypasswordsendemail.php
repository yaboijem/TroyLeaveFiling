<?php
    require 'vendor/autoload.php'; // Load PHPMailer
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    include('sendingemailused.php'); 

    $email = $email; // Assuming $email is already defined and contains the employee's email address
    $employeeName = $employeeName;
    $tempPassword = $newPassword;

    $newHashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE employeefiletbl SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $newHashedPassword, $email);
    $stmt->execute();
    $stmt->close();
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        //Server settings
        $mail->SMTPDebug = 0; // Enable verbose debug output.  Set to 0 for production.
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; //Set the SMTP server to send through
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = $userNamePHPEmail; //SMTP username
        $mail->Password   = $passwordPHPEmail; //SMTP password (App Password, NOT your regular password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 465; // TCP port to connect to; use 587 if you have set `$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom($userNamePHPEmail, 'No Reply');
        $mail->addAddress($email); // Add a recipient
        
        //Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'HRIS Temporary Password';
        //$mail->Body    = 'Need to Approve of Leave<br><br>'.
                            // 'Cheers.<br>'.
                            // 'Troy  Management';
        include('domainname.php');
        $mail->Body = "
            <p>Dear $employeeName,</p>
            <p>Your temporary password has been generated for accessing the system. Please use the credentials below to log in:</p>
            
            <p><strong>Login Details:</strong></p>
            <ul>
                <li><strong>Employee:</strong> $employeeName</li>
                <li><strong>Username:</strong> $email</li>
                <li><strong>Temporary Password:</strong> <strong>$tempPassword</strong></li>
                <li><strong>Generated On:</strong> " . date('F j, Y g:i A') . "</li>
            </ul>

            <p><strong>Important:</strong> For your security, please change this temporary password immediately after logging in.</p>
            <p>Please log in to the HRIS portal to review and take action.</p>
            <p><a href='".$domainName."dashboard.php#'>Click Here!</a></p>

            <p>If you did not request this password or believe this message was sent in error, please contact the HR or IT department immediately.</p>

            <p>Thank you,<br>
            <strong>HR Department</strong></p>
        ";
        
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';

        $mail->send();
        echo "Password reset successful!";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
                
    // echo "✅ Successfully Update and Generated Temporary Password!";
    // echo "Processed " . count($records) . " records. ";
    

    //for vb6 doon sa sending email temporary password
    // require 'vendor/autoload.php'; // Load PHPMailer
    // use PHPMailer\PHPMailer\PHPMailer;
    // use PHPMailer\PHPMailer\Exception;

    // include('apikey.php');
    // // Get headers
    // $headers = getallheaders();
    // if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $API_KEY) {
    //     http_response_code(403);
    //     echo json_encode(["error" => "Unauthorized request"]);
    //     exit;
    // }

    // $json = file_get_contents("php://input");

    // // Basic validation
    // if (empty($json)) {
    //     http_response_code(400);
    //     echo json_encode(["error" => "No JSON data received"]);
    //     exit;
    // }

    // $records = json_decode($json, true);

    // // Validate JSON structure
    // if (json_last_error() !== JSON_ERROR_NONE || !is_array($records)) {
    //     http_response_code(400);
    //     echo json_encode(["error" => "Invalid JSON format"]);
    //     exit;
    // }

    // // Extra security: ensure required fields exist
    // foreach ($records as $row) {
    //     if (!isset($row['idno'], $row['tempPassword'])) {
    //         http_response_code(400);
    //         echo json_encode(["error" => "Missing required fields"]);
    //         exit;
    //     }
    // }

    // // if (isset($_POST['data'])) {
    // //     $json = $_POST['data'];
    // //     $records = json_decode($json, true);

    //     include("dbconnect.php");
    //     include('sendingemailused.php'); 

    //     if (is_array($records)) {
    //         $missingIds = []; // Array to store non-existent IDs

    //         foreach ($records as $row) {
    //             $email = $row['email'];
    //             $employeeName = $row['employeeName'];
    //             $tempPassword = $row['tempPassword'];

    //             $newHashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    //             $stmt = $conn->prepare("UPDATE employeefiletbl SET password = ? WHERE email = ?");
    //             $stmt->bind_param("ss", $newHashedPassword, $email);
    //             $stmt->execute();
    //             $stmt->close();
    //             $mail = new PHPMailer(true); // Passing `true` enables exceptions

    //             try {
    //                 //Server settings
    //                 $mail->SMTPDebug = 0; // Enable verbose debug output.  Set to 0 for production.
    //                 $mail->isSMTP();
    //                 $mail->Host       = 'smtp.gmail.com'; //Set the SMTP server to send through
    //                 $mail->SMTPAuth   = true; // Enable SMTP authentication
    //                 $mail->Username   = $userNamePHPEmail; //SMTP username
    //                 $mail->Password   = $passwordPHPEmail; //SMTP password (App Password, NOT your regular password)
    //                 $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    //                 $mail->Port       = 465; // TCP port to connect to; use 587 if you have set `$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //                 //Recipients
    //                 $mail->setFrom($userNamePHPEmail, 'No Reply');
    //                 $mail->addAddress($email); // Add a recipient
                    
    //                 //Content
    //                 $mail->isHTML(true); // Set email format to HTML
    //                 $mail->Subject = 'HRIS Temporary Password';
    //                 //$mail->Body    = 'Need to Approve of Leave<br><br>'.
    //                                     // 'Cheers.<br>'.
    //                                     // 'Troy  Management';
    //                 include('domainname.php');
    //                 $mail->Body = "
    //                     <p>Dear $employeeName,</p>
    //                     <p>Your temporary password has been generated for accessing the system. Please use the credentials below to log in:</p>
                        
    //                     <p><strong>Login Details:</strong></p>
    //                     <ul>
    //                         <li><strong>Employee:</strong> $employeeName</li>
    //                         <li><strong>Username:</strong> $email</li>
    //                         <li><strong>Temporary Password:</strong> <strong>$tempPassword</strong></li>
    //                         <li><strong>Generated On:</strong> " . date('F j, Y g:i A') . "</li>
    //                     </ul>

    //                     <p><strong>Important:</strong> For your security, please change this temporary password immediately after logging in.</p>
    //                     <p>Please log in to the HRIS portal to review and take action.</p>
    //                     <p><a href='".$domainName."dashboard.php#'>Click Here!</a></p>

    //                     <p>If you did not request this password or believe this message was sent in error, please contact the HR or IT department immediately.</p>

    //                     <p>Thank you,<br>
    //                     <strong>HR Department</strong></p>
    //                 ";
                    
    //                 $mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';

    //                 $mail->send();
    //             } catch (Exception $e) {
    //                 echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    //             }
                   
    //         }
    //         echo "✅ Successfully Update and Generated Temporary Password!";
    //         echo "Processed " . count($records) . " records. ";
    //     } else {
    //         echo "Invalid JSON";
    //     }
    // // } else {
    // //     echo "No data received";
    // // }
?>