<?php
// includes/delete_address.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $address_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Verify ownership before deleting
        $stmt = $pdo->prepare("DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?");
        $stmt->execute([$address_id, $user_id]);

        header("Location: ../profile.php?tab=addresses");
        exit();

    } catch (PDOException $e) {
        die("Error deleting address: " . $e->getMessage());
    }
} else {
    header("Location: ../profile.php?tab=addresses");
    exit();
}
?>