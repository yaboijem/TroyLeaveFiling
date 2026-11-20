<?php
@ob_start(); if (!isset($_SESSION)) {session_start();}
//echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'unknown';
if ($_SESSION['admin_or_user'] === "admin") {
    echo "1";
} else if ($_SESSION['admin_or_user'] === "approver") {
    echo "2";
} else {
    echo "3";
}
?>

