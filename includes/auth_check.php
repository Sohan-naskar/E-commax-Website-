<?php
// includes/auth_check.php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit(); // Always exit after redirect
}
?>