<?php
// debug_user.php
session_start();
require 'config/database.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "User ID: " . $_SESSION['user_id'] . "\n";
    print_r($user);
} else {
    echo "No user logged in.\n";
}
?>