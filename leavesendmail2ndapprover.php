<?php
    if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
        echo "<script>window.open('index.php','_self')</script>";
    }
    
    

    require 'vendor/autoload.php'; // Assuming you've installed PHPMailer via Composer

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    include('sendingemailused.php');

    // if(isset($_GET['emplEmail']) && isset($_GET['approver1']) && isset($_GET['approver2']) && isset($_GET['emailType'])){
        $mail = new PHPMailer(true); // Passing `true` enables exceptions
        date_default_timezone_set('Asia/Manila');

        try {
            if ($emailType === "Approved" || $emailType === "Disapproved") {
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
                $mail->addAddress($emplEmail); // Add a recipient
                
                //Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = 'Leave Request Status';
                if ((float)$noOfDays < (float)1)  {
                    $leaveDate = $fromDate." (Half Day)";
                } else {
                    $leaveDate = $fromDate." to ".$toDate;
                }
                
                if ($emailType === "Approved") {
                    $mail->Body = "
                                    <p>Dear $employeeName,</p>
                                    <p>Your leave request has been <strong>approved</strong> by $approverName.</p>

                                    <p><strong>Leave Details:</strong></p>
                                    <ul>
                                        <li><strong>Employee:</strong> $employeeName</li>
                                        <li><strong>Leave Type:</strong> $leaveName</li>
                                        <li><strong>Leave Date:</strong> $leaveDate</li>
                                        <li><strong>Reason/Replacement:</strong> $remark</li>
                                        <li><strong>Date Filed:</strong> $dateFiled</li>
                                        <li><strong>Approved On:</strong> " . date('F j, Y g:i A') . "</li>
                                    </ul>

                                    <p>Please ensure coordination with your team before your leave date.</p>

                                    <p>Thank you,<br>
                                    <strong>HR Department</strong><br></p>
                                ";
                    $messageStatus = "Successfully Approved!";
                } else { //Disapproved
                    $mail->Body = "
                                    <p>Dear $employeeName,</p>
                                    <p>We regret to inform you that your leave request has been <strong>disapproved</strong> by <strong>$approverName</strong>.</p>

                                    <p><strong>Leave Details:</strong></p>
                                    <ul>
                                        <li><strong>Employee:</strong> $employeeName</li>
                                        <li><strong>Leave Type:</strong> $leaveName</li>
                                        <li><strong>Leave Date:</strong> $leaveDate</li>
                                        <li><strong>Reason for Filing:</strong> $remark</li>
                                        <li><strong>Date Filed:</strong> $dateFiled</li>
                                        <li><strong>Disapproved On:</strong> " . date('F j, Y g:i A') . "</li>
                                    </ul>

                                    <p><strong>Approver's Remarks:</strong></p>
                                    <p style='margin-left:20px;'>$disapproveReason</p>

                                    <p>If you have any concerns or would like to discuss this decision, please reach out to your approver or HR representative.</p>

                                    <p>Thank you for your understanding.<br>
                                    <strong>HR Leave System</strong><br></p>
                                ";
                    $messageStatus = "Successfully Disapproved!";
                }

                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';

                $mail->send();
                echo $messageStatus;
                //echo '<p id="message">The message has been sent.</p>';
            } else if ($emailType === "Follow-up") {
                // echo $userNamePHPEmail. " | ".$passwordPHPEmail. " | " .$approverEmail. " | " .$fEmployeeName. " | " .$fLeaveName. " | " .$fLeaveDate. " | " .$fRemark. " | " .$fDateFiled;     
                // exit;
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
                $mail->addAddress($approverEmail); // Add a recipient

                //Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = 'Follow-up on Leave Request Approval';
                
                include('domainname.php');
                $mail->Body = "
                    <p>Dear Sir/Ma'am,</p>
                    <p>This is a follow-up regarding the pending leave request that requires your approval.</p>
                    <p><strong>Leave Details:</strong></p>
                    <ul>
                        <li>Employee: $fEmployeeName</li>
                        <li>Leave Type: $fLeaveName</li>
                        <li>Leave Dates: $fLeaveDate</li>
                        <li>Reason: $fRemark</li>
                        <li>Date Filed: " . $fDateFiled . "</li>
                    </ul>
                    <p>Please log in to the HRIS portal to review and take action on this leave request at your earliest convenience.</p>
                    <a href='".$domainName."dashboard.php#'>Click Here!</a><br><br>
                    <p>Thank you,<br>HR Department</p>";
                
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';

                $mail->send();
            } else { //Leave Filing Request for Approval
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
                if ($approver1 === $approver2) { // If both approvers are the same, only add one recipient
                    $mail->addAddress($approver1); // Add a recipient
                } else {
                    $mail->addAddress($approver1); // Add a recipient

                    // Commented out to avoid sending to the second approver kasi first approver is the one who needs to approve before the second approver can approve.  This is to avoid confusion and unnecessary emails.
                    //$mail->addAddress($approver2); // Add a recipient 
                }
                
                //Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = 'Leave Request for Approval';
                //$mail->Body    = 'Need to Approve of Leave<br><br>'.
                                    // 'Cheers.<br>'.
                                    // 'Troy  Management';
                if ((float)$noOfDays < (float)1)  {
                    $leaveDate = $fromDate." (Half Day)";
                } else {
                    if ($fromDate === $toDate) {
                        $leaveDate = $fromDate;
                    } else {
                         $leaveDate = $fromDate." to ".$toDate;
                    }
                }
                
                include('domainname.php');
                $mail->Body = "
                    <p>Dear Sir/Ma'am,</p>
                    <p>$employeeName has filed a leave request that requires your approval.</p>
                    <p><strong>Leave Details:</strong></p>
                    <ul>
                        <li>Employee: $employeeName</li>
                        <li>Leave Type: $leaveName</li>
                        <li>Leave Dates: $leaveDate</li>
                        <li>Reason: $remark</li>
                        <li>Date Filed: " . date('F j, Y g:i A') . "</li>
                    </ul>
                    <p>Please log in to the HRIS portal to review and take action.</p>
                    <a href='".$domainName."dashboard.php#'>Click Here!</a><br><br>
                    <p>Thank you,<br>HR Department</p>";
                
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';

                $mail->send();
                // echo "Successfully Disapproved!";
                //echo '<p id="message">The message has been sent.</p>';
            }
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    // } else {
    //     echo '<p id="message">The message has not been sent.</p>';
    // }
?>