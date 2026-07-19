<?php
// logout.php
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['cart']);
unset($_SESSION['checkout_data']);
// session_destroy(); // Do not destroy session to keep Admin logged in
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'login.php';
header("Location: $redirect_to");
exit();
?>