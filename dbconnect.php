<?php
    @ob_start();
    // if(session_status()!=PHP_SESSION_ACTIVE) session_start();
    // if (!isset($_SESSION)) {session_start();}
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "troyserverpayroll"; //restore
    
    // //  Hosting
    //  $servername = "localhost";
    //  $username = "u472850317";
    //  $password = "K@test123";
    //  $dbname = "u472850317db";
    
    
    // Create connection
    $conn = new mysqli($servername, $username, $password,$dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // echo "Connected successfully";
    //echo    $_SESSION['log_in_attempt'];
    //Itong code na ito ay para hindi muna dumaan sa verifytoken.php kapag naglogin palang
    if (isset($_SESSION['log_in_attempt']) && $_SESSION['log_in_attempt'] === "N") {
        include('verifytoken.php');
    } elseif (isset($_SESSION['log_in_attempt']) && $_SESSION['log_in_attempt'] === "Y") {
        $_SESSION['log_in_attempt'] = "N"; 
    }
?>