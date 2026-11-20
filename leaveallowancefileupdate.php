<?php
    // header('Content-Type: text/plain; charset=UTF-8');
    // if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    // if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
    //     echo "<script>window.open('index.php','_self')</script>";
    // }
    // if (isset($_POST['data'])) {
        // $json = $_POST['data'];
        // $records = json_decode($json, true);
        // Secret API key
        include('apikey.php');
        // Get headers
        $headers = getallheaders();
        if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $API_KEY) {
            http_response_code(403);
            echo json_encode(["error" => "Unauthorized request"]);
            exit;
        }

        $json = file_get_contents("php://input");

        // Basic validation
        if (empty($json)) {
            http_response_code(400);
            echo json_encode(["error" => "No JSON data received"]);
            exit;
        }

        $records = json_decode($json, true);

        // Validate JSON structure
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($records)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON format"]);
            exit;
        }

        // Extra security: ensure required fields exist
        foreach ($records as $row) {
            if (!isset($row['idno'], $row['leavecode'], $row['leavename'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required fields"]);
                exit;
            }
        }
        

        include("dbconnect.php");
        
        if (is_array($records)) {
            foreach ($records as $row) {
                $idno = $row['idno'];
                $leavecode = $row['leavecode'];
                $leavename = $row['leavename'];
                $allowance = $row['allowance'];
                $use = $row['use'];
                $balance = $row['balance'];
                
                $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM leaveallowancefiletbl WHERE idno = ? AND leavecode = ?");
                $checkStmt->bind_param("ss", $idno, $leavecode);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();

                if ($count > 0) {
                    // Update if exists
                    $updateStmt = $conn->prepare("
                        UPDATE leaveallowancefiletbl 
                        SET leavename = ?, allowance = ?, `use` = ?, balance = ? WHERE idno = ? AND leavecode = ?");
                    $updateStmt->bind_param("ssssss", $leavename, $allowance, $use, $balance, $idno, $leavecode);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert if not exists
                    $insertStmt = $conn->prepare("
                        INSERT INTO leaveallowancefiletbl 
                        (idno, leavecode, leavename, allowance, `use`, balance) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->bind_param("ssssss",  $idno, $leavecode, $leavename, $allowance, $use, $balance);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
            }
            echo "Processed " . count($records) . " records.";
        } else {
            echo "Invalid JSON";
        }
    // } else {
    //     echo "No data received";
    // }   
?>