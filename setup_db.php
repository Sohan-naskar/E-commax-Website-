<?php
// setup_db.php
// Run this file once to create the database and table

$host = 'localhost';
$username = 'root';
$password = '12345';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce_db");
    echo "Database created successfully.<br>";

    // Select Database
    $pdo->exec("USE ecommerce_db");

    // Create Customers Table
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'customers' created successfully.<br>";

} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>