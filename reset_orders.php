<?php
// reset_orders.php
require_once 'config/database.php';

try {
    // Disable foreign key checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Clear order_items first
    $pdo->exec("TRUNCATE TABLE order_items");
    echo "Order items cleared.<br>";

    // Clear orders (this resets AUTO_INCREMENT to 1)
    $pdo->exec("TRUNCATE TABLE orders");
    echo "Orders table cleared and auto-increment reset to 1.<br>";

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

} catch (PDOException $e) {
    echo "Error resetting orders: " . $e->getMessage();
}
?>