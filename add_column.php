<?php
require_once 'config/database.php';

try {
    $sql = "ALTER TABLE order_items ADD COLUMN status ENUM('pending', 'cancelled', 'delivered') DEFAULT 'pending'";
    $pdo->exec($sql);
    echo "Column 'status' added successfully to 'order_items'.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>