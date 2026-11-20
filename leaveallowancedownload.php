<?php
    // Secret API key
    include('apikey.php');
    // Get headers
    $headers = getallheaders();
    if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $API_KEY) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized request"]);
        exit;
    }

    include("dbconnect.php");  // ✅ adjust to your DB
    
    // Prepare query
    $sql = "SELECT `idno`, `leavecode`,  
                `allowance`, `use`, `balance`
            FROM `leaveallowancefiletbl`";
            // WHERE approver1status = 1 and approver2status = 1 and download = 0  
            // AND (FromDate <= '$cutoffTo' AND ToDate >= '$cutoffFrom')";
    $result = $conn->query($sql);

    $rows = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    // Return JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows);
?>
