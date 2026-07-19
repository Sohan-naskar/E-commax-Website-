<?php
require 'config/database.php';
try {
    echo "--- CUSTOMERS ---\n";
    $stmt = $pdo->query("DESCRIBE customers");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n--- ORDERS ---\n";
    $stmt = $pdo->query("DESCRIBE orders");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>