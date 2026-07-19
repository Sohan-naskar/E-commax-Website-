<?php
require 'c:/xampp/htdocs/PHP_College_5.2/Google_Antigravity/config/database.php';
try {
    echo "--- order_items ---\n";
    $stmt = $pdo->query("DESCRIBE order_items");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "--- orders ---\n";
    $stmt = $pdo->query("DESCRIBE orders");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>