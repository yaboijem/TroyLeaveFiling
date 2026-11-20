<?php
    // header('Content-Type: text/plain; charset=UTF-8');
    // if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    // if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
    //     echo "<script>window.open('index.php','_self')</script>";
    // }
    if (isset($_POST['data'])) {
        $json = $_POST['data'];
        $records = json_decode($json, true);

        include("dbconnect.php");
        
        if (is_array($records)) {
            foreach ($records as $row) {
                $tranno = $row['tranno'];
                $trandate = $row['trandate'];
                $idno = $row['idno'];
                $leavecode = $row['leavecode'];
                $deptcode = $row['deptcode'];
                $divcode = $row['divcode'];
                $noofdays = $row['noofdays'];
                $noofhours = $row['noofhours'];
                $fromdate = $row['fromdate'];
                $todate = $row['todate'];
                $totalnoofhours = $row['totalnoofhours'];
                $remark = $row['remark'];
                $allowance = $row['allowance'];
                $use = $row['use'];
                $balance = $row['balance'];
                $linenum = $row['linenum'];
                $inputuser = $row['inputuser'];
                $amount = $row['amount'];
                $commutation = $row['commutation'];
                $yeardeduct = $row['yeardeduct'];
                $noeffectlb = $row['noeffectlb'];
                    
                $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM leavefilingfiletbl WHERE tranno = ?");
                $checkStmt->bind_param("s", $tranno);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();

                if ($count > 0) {
                    // Update if exists
                    $updateStmt = $conn->prepare("
                        UPDATE leavefilingfiletbl 
                        SET trandate = ?,idno = ?,leavecode = ?,deptcode = ?,divcode = ?,
                        noofdays = ?,noofhours = ?,fromdate = ?,todate = ?,totalnoofhours = ?,remark = ?, 
                        allowance = ?, `use` = ?, balance = ?,linenum = ?,inputuser = ?,amount = ?, 
                        commutation = ?, yeardeduct = ?, noeffectlb = ? WHERE tranno = ?");
                    $updateStmt->bind_param("ssssssssssssssssssss", $trandate, $idno, $leavecode, $deptcode, $divcode, 
                                $noofdays, $noofhours, $fromdate, $todate, $totalnoofhours, $remark, 
                                $allowance, $use, $balance, $linenum, $inputuser, $amount, 
                                $commutation, $yeardeduct, $noeffectlb, $tranno);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert if not exists
                    $insertStmt = $conn->prepare("
                        INSERT INTO leavefilingfiletbl 
                        (tranno, trandate, idno, leavecode, deptcode, divcode, 
                        noofdays, noofhours, fromdate, todate, totalnoofhours, remark,  
                        allowance, `use`,  balance, linenum, inputuser, amount,  
                        commutation, yeardeduct, noeffectlb ) 
                        VALUES (?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->bind_param("sssssssssssssssssssss",  $tranno, $trandate, $idno, $leavecode, $deptcode, $divcode, 
                                $noofdays, $noofhours, $fromdate, $todate, $totalnoofhours, $remark, 
                                $allowance, $use, $balance, $linenum, $inputuser, $amount, 
                                $commutation, $yeardeduct, $noeffectlb);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
            }
            echo "Processed " . count($records) . " records.";
        } else {
            echo "Invalid JSON";
        }
    } else {
        echo "No data received";
    }   
?>