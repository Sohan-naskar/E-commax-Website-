<?php
session_start();
include 'auth_check.php';
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);

        // Redirect back with success message
        header("Location: index.php?msg=Order cancelled successfully");
        exit();
    } catch (PDOException $e) {
        die("Error cancelling order: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>