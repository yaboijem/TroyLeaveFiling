<?php
    if (isset($_POST['data'])) {
        $json = $_POST['data'];
        $records = json_decode($json, true);

        include("dbconnect.php");
        
        if (is_array($records)) {
            foreach ($records as $row) {
                $idno = $row['idno'];
                $employeeName = $row['employeeName'];
                $email = $row['email'];
                $tempPassword = $row['tempPassword'];
                
                // Update if exists
                $updateStmt = $conn->prepare("
                    UPDATE employeefiletbl 
                    SET employeename = ?, deptcode = ?, divcode = ?, employmentstatus = ?, 
                        position = ?, email = ?, approver1 = ?, approver2 = ?, usertype = ?
                    WHERE idno = ?
                ");
                
                $updateStmt->bind_param("ssssssssss", $employeename, $deptcode, $divcode, $employmentstatus,
                    $position, $email, $approver1, $approver2, $usertype, $idno);
                $updateStmt->execute();
                $updateStmt->close();
                
            }
            echo "Processed " . count($records) . " records.";
        } else {
            echo "Invalid JSON";
        }
    } else {
        echo "No data received";
    }   
?>