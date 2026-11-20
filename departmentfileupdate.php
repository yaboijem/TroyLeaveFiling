<?php
    // if (isset($_POST['data'])) {
    //     $json = $_POST['data'];
    //     $records = json_decode($json, true);

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
            if (!isset($row['deptcode'], $row['deptname'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required fields"]);
                exit;
            }
        }

        include("dbconnect.php");
        
        if (is_array($records)) {
            foreach ($records as $row) {
                $deptcode = $row['deptcode'];
                $deptname = $row['deptname'];
                $remark = $row['remark'];
                $accountcode = $row['accountcode'];
                

                // Check if ID exists
                $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM departmentfiletbl WHERE deptcode = ?");
                $checkStmt->bind_param("s", $deptcode);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();

                if ($count > 0) {
                    // Update if exists
                    $updateStmt = $conn->prepare("
                        UPDATE departmentfiletbl 
                        SET deptname = ?, remark = ?, accountcode = ?
                        WHERE deptcode = ?
                    ");
                    $updateStmt->bind_param("ssss", $deptname, $remark, $accountcode, $deptcode);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert if not exists
                    $insertStmt = $conn->prepare("
                        INSERT INTO departmentfiletbl 
                        (deptcode, deptname, remark, accountcode) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertStmt->bind_param("ssss", $deptcode, $deptname, $remark, $accountcode);
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