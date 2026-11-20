<?php
    header("Content-Type: application/json"); // Always return JSON response

    include("dbconnect.php");
    
    try {
        // Kunin ang raw JSON mula sa request body
        $json = file_get_contents("php://input");
        $records = json_decode($json, true);

        if (!is_array($records)) {
            echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
            exit;
        }

        $processed = 0;

        foreach ($records as $row) {
            // Sanitize inputs
            $idno             = $row['idno'] ?? "";
            $employeename     = $row['employeename'] ?? "";
            $deptcode         = $row['deptcode'] ?? "";
            $divcode          = $row['divcode'] ?? "";
            $position         = $row['position'] ?? "";
            $active           = $row['active'] ?? "";
            $employmentstatus = $row['employmentstatus'] ?? "";
            $email            = $row['email'] ?? "";
            $approver1        = $row['approver1'] ?? "";
            $approver2        = $row['approver2'] ?? "";
            $usertype         = $row['usertype'] ?? "";

            if (empty($idno)) {
                // Skip kung walang ID
                continue;
            }

            // Check if ID exists
            $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM employeefiletbl WHERE idno = ?");
            $checkStmt->bind_param("s", $idno);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                // Update if exists
                $updateStmt = $conn->prepare("
                    UPDATE employeefiletbl 
                    SET employeename = ?, deptcode = ?, divcode = ?, employmentstatus = ?, 
                        position = ?, active = ?, email = ?, approver1 = ?, approver2 = ?, usertype = ?
                    WHERE idno = ?
                ");
                $updateStmt->bind_param(
                    "sssssssssss", 
                    $employeename, $deptcode, $divcode, $employmentstatus,
                    $position, $active, $email, $approver1, $approver2, $usertype, $idno
                );
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert if not exists
                $insertStmt = $conn->prepare("
                    INSERT INTO employeefiletbl 
                    (idno, employeename, deptcode, divcode, employmentstatus, position, active, email, approver1, approver2, usertype) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->bind_param(
                    "sssssssssss", 
                    $idno, $employeename, $deptcode, $divcode, $employmentstatus,
                    $position, $active, $email, $approver1, $approver2, $usertype
                );
                $insertStmt->execute();
                $insertStmt->close();
            }

            $processed++;
        }

        echo json_encode([
            "status" => "success transferred",
            "message" => "Processed $processed records"
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }


    // // Kunin ang raw JSON mula sa request body
    // $json = file_get_contents("php://input");
    // $records = json_decode($json, true);

    // include("dbconnect.php");
    
    // if (is_array($records)) {
    //     foreach ($records as $row) {
    //         $idno = $row['idno'];
    //         $employeename = $row['employeename'];
    //         $deptcode = $row['deptcode'];
    //         $divcode = $row['divcode'];
    //         $position = $row['position'];
    //         $active = $row['active'];
    //         $employmentstatus = $row['employmentstatus'];
    //         $email = $row['email'];
    //         $approver1 = $row['approver1'];
    //         $approver2 = $row['approver2'];
    //         $usertype = $row['usertype'];

    //         // Check if ID exists
    //         $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM employeefiletbl WHERE idno = ?");
    //         $checkStmt->bind_param("s", $idno);
    //         $checkStmt->execute();
    //         $checkStmt->bind_result($count);
    //         $checkStmt->fetch();
    //         $checkStmt->close();

    //         if ($count > 0) {
    //             // Update if exists
    //             $updateStmt = $conn->prepare("
    //                 UPDATE employeefiletbl 
    //                 SET employeename = ?, deptcode = ?, divcode = ?, employmentstatus = ?, 
    //                     position = ?, active = ?, email = ?, approver1 = ?, approver2 = ?, usertype = ?
    //                 WHERE idno = ?
    //             ");
    //             $updateStmt->bind_param("sssssssssss", $employeename, $deptcode, $divcode, $employmentstatus,
    //                 $position, $active, $email, $approver1, $approver2, $usertype, $idno);
    //             $updateStmt->execute();
    //             $updateStmt->close();
    //         } else {
    //             // Insert if not exists
    //             $insertStmt = $conn->prepare("
    //                 INSERT INTO employeefiletbl 
    //                 (idno, employeename, deptcode, divcode, employmentstatus, position, active, email, approver1, approver2, usertype) 
    //                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    //             ");
    //             $insertStmt->bind_param("sssssssssss", $idno, $employeename, $deptcode, $divcode, $employmentstatus,
    //                 $position, $active, $email, $approver1, $approver2, $usertype);
    //             $insertStmt->execute();
    //             $insertStmt->close();
    //         }
    //     }

    //     echo "Processed " . count($records) . " records.";
    // } else {
    //     echo "Invalid JSON";
    // }


    // // header('Content-Type: text/plain; charset=UTF-8');
    // // if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    // // if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
    // //     echo "<script>window.open('index.php','_self')</script>";
    // // }
    // if (isset($_POST['data'])) {
    //     $json = $_POST['data'];
    //     $records = json_decode($json, true);

    //     include("dbconnect.php");
    
    //     if (is_array($records)) {
    //         foreach ($records as $row) {
    //             $idno = $row['idno'];
    //             $employeename = $row['employeename'];
    //             $deptcode = $row['deptcode'];
    //             $divcode = $row['divcode'];
    //             $position = $row['position'];
    //             $active = $row['active'];
    //             $employmentstatus = $row['employmentstatus'];
    //             $email = $row['email'];
    //             $approver1 = $row['approver1'];
    //             $approver2 = $row['approver2'];
    //             $usertype = $row['usertype'];

    //             // Check if ID exists
    //             $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM employeefiletbl WHERE idno = ?");
    //             $checkStmt->bind_param("s", $idno);
    //             $checkStmt->execute();
    //             $checkStmt->bind_result($count);
    //             $checkStmt->fetch();
    //             $checkStmt->close();

    //             if ($count > 0) {
    //                 // Update if exists
    //                 $updateStmt = $conn->prepare("
    //                     UPDATE employeefiletbl 
    //                     SET employeename = ?, deptcode = ?, divcode = ?, employmentstatus = ?, 
    //                         position = ?, active = ?, email = ?, approver1 = ?, approver2 = ?, usertype = ?
    //                     WHERE idno = ?
    //                 ");
    //                 $updateStmt->bind_param("sssssssssss", $employeename, $deptcode, $divcode, $employmentstatus,
    //                     $position, $active, $email, $approver1, $approver2, $usertype, $idno);
    //                 $updateStmt->execute();
    //                 $updateStmt->close();
    //             } else {
    //                 // Insert if not exists
    //                 $insertStmt = $conn->prepare("
    //                     INSERT INTO employeefiletbl 
    //                     (idno, employeename, deptcode, divcode, employmentstatus, position, active, email, approver1, approver2, usertype) 
    //                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    //                 ");
    //                 $insertStmt->bind_param("sssssssssss", $idno, $employeename, $deptcode, $divcode, $employmentstatus,
    //                     $position, $active, $email, $approver1, $approver2, $usertype);
    //                 $insertStmt->execute();
    //                 $insertStmt->close();
    //             }


    //         }
    //         echo "Processed " . count($records) . " records.";
    //     } else {
    //         echo "Invalid JSON";
    //     }
    // } else {
    //     echo "No data received";
    // }   
?>