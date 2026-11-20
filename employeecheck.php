<?php
    //if (isset($_POST['data'])) {
        // $json = $_POST['data'];
        // $records = json_decode($json, true);
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
            if (!isset($row['idno'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required fields"]);
                exit;
            }
        }


        include("dbconnect.php");
        
        if (is_array($records)) {
            $missingIds = []; // Array to store non-existent IDs

            foreach ($records as $row) {
                $idno = $row['idno'];

                // Check if ID exists
                $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM employeefiletbl WHERE idno = ?");
                $checkStmt->bind_param("s", $idno);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->close();

                if ($count == 0) {
                    $missingIds[] = $idno;
                }
            }

            echo "Processed " . count($records) . " records. ";

            if (!empty($missingIds)) {
                echo "The following employee IDs do not exist: ";
                echo implode(", ", $missingIds);
                echo ". Cannot proceed sending temporary online password!";
            } else {
                echo "All employee IDs exist.";
            }
        } else {
            echo "Invalid JSON";
        }
    // } else {
    //     echo "No data received";
    // }
?>