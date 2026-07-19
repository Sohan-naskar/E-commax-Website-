<?php
require 'config/database.php';

try {
    // 1. Alter Customers Table
    $sql = "ALTER TABLE customers 
            ADD COLUMN pincode VARCHAR(10) DEFAULT NULL,
            ADD COLUMN locality VARCHAR(100) DEFAULT NULL,
            ADD COLUMN city VARCHAR(50) DEFAULT NULL,
            ADD COLUMN state VARCHAR(50) DEFAULT NULL,
            ADD COLUMN landmark VARCHAR(100) DEFAULT NULL,
            ADD COLUMN alternate_phone VARCHAR(20) DEFAULT NULL,
            ADD COLUMN address_type ENUM('Home', 'Work') DEFAULT 'Home'";

    // Check if columns exist to avoid error
    // Simple way: try catch or just run and ignore specific error
    try {
        $pdo->exec($sql);
        echo "Customers table updated.\n";
    } catch (PDOException $e) {
        echo "Customers table update skipped (maybe cols exist): " . $e->getMessage() . "\n";
    }

    // 2. Alter Orders Table
    // Add shipping_address to snapshot the address at time of order
    $sql = "ALTER TABLE orders 
            ADD COLUMN shipping_address TEXT DEFAULT NULL,
            ADD COLUMN shipping_name VARCHAR(100) DEFAULT NULL,
            ADD COLUMN shipping_phone VARCHAR(20) DEFAULT NULL";

    try {
        $pdo->exec($sql);
        echo "Orders table updated.\n";
    } catch (PDOException $e) {
        echo "Orders table update skipped: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>