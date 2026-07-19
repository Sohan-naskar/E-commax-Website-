<?php
// admin/auth_check.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    // Redirect to admin login page
    header("Location: ../login.php");
    exit();
}
?>