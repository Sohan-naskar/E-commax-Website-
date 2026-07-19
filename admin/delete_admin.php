<?php
// admin/delete_admin.php
include 'auth_check.php';
include '../config/database.php';

$id = $_GET['id'] ?? null;
$current_admin_id = $_SESSION['admin_id'];

if ($id) {
    if ($id == $current_admin_id) {
        // Prevent self-deletion
        echo "<script>alert('You cannot delete your own account!'); window.location.href='index.php';</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
}

header("Location: index.php");
exit();
?>