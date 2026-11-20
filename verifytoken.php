<?php
    //session_start();
    // include("dbconnect.php");

    if (!isset($_SESSION['idno']) || !isset($_SESSION['session_token'])) {
        header("Location: index.php");
        exit;
    }

    // //kung ang id ay 99999 ay hindi dapat i-check ang session token dahil ito ay admin account
    // if ($_SESSION['idno'] === 1015832026) {
    //     //session_destroy();
    //     // exit;
    // } else {

        $stmt = $conn->prepare("SELECT session_token FROM employeefiletbl WHERE idno = ?");
        $stmt->bind_param("s", $_SESSION['idno']);
        $stmt->execute();
        $stmt->bind_result($db_token);
        $stmt->fetch();
        $stmt->close();

        // ✅ Check if the current session matches DB
        if ($db_token !== $_SESSION['session_token']) {
            // Someone else logged in → kick this session
            session_unset();
            session_destroy();
            // echo "<script>alert('You are already logged in on another device. Multiple logins are not allowed.');";
            //header("Location: index.php?error=session_expired");
            echo "<script>window.top.location.href='index.php?error=session_expired';</script>";
            exit;
        }
    // }
?>