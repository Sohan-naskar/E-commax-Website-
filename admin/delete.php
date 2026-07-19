<?php
// admin/delete.php
include '../config/database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
}

header("Location: index.php");
exit();
?>