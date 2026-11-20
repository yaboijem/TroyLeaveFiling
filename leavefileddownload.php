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
    
    $cutoffFrom = date('Y-m-d', strtotime($_GET['fromdate']));
    $cutoffTo = date('Y-m-d', strtotime($_GET['todate']));;
    // Prepare query
    $sql = "SELECT `id`, `tranno`, `trandate`, `idno`, `leavecode`, `deptcode`, `divcode`,
                `noofdays`, `noofhours`, `fromdate`, `todate`, `totalnoofhours`, `remark`, 
                `allowance`, `use`, `balance`, `lineNum`, `inputuser`, `amount`, 
                `commutation`, `yeardeduct`, `leaverequestfor`, `created` 
            FROM `leavefilingfiletbl` 
            WHERE approver1status = 1 and approver2status = 1 and download = 0  
            AND (FromDate <= '$cutoffTo' AND ToDate >= '$cutoffFrom')";
            // AND ((FromDate BETWEEN '$cutoffFrom' AND '$cutoffTo')
            //     OR (ToDate BETWEEN '$cutoffFrom' AND '$cutoffTo')
            //     OR (FromDate <= '$cutoffFrom' AND ToDate >= '$cutoffTo'))";

            // 
    
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
