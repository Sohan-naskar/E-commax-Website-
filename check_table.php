<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_addresses'");
    if ($stmt->rowCount() > 0) {
        echo "Existent";
    } else {
        echo "Missing";
    }
} catch (Exception $e) {
    echo "Error";
}
?>