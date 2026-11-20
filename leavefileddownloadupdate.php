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

    //$json = $_POST['data'];
    //$records = json_decode($json, true);

    include("dbconnect.php");
    
    
    // Update if exists
    $updateStmt = $conn->prepare("
        UPDATE leavefilingfiletbl 
        SET download = 1 WHERE download = 0");
    // $updateStmt->bind_param("ssssssssss", $employeename, $deptcode, $divcode, $employmentstatus,
    //                         $position, $email, $approver1, $approver2, $usertype, $idno);
    $updateStmt->execute();
    $updateStmt->close();   
?>
