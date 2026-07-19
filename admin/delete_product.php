<?php
// admin/delete_product.php
include 'auth_check.php';
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        // Check if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count == 0) {
            $pdo->query("ALTER TABLE products AUTO_INCREMENT = 1");

            // Reset Orders as requested (Total, Paid, Unpaid)
            $pdo->query("TRUNCATE TABLE orders");
            $pdo->query("TRUNCATE TABLE order_items"); // Assuming this table exists, safer to include
        }

        // Redirect back to products tab
        header("Location: index.php?product_deleted=1");
        exit();
    } catch (PDOException $e) {
        die("Error deleting product: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit();
}
?>