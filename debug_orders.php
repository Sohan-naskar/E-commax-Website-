<?php
require_once 'config/database.php';

echo "Database Name: " . $db_name . "<br>";

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$count = $stmt->fetchColumn();
echo "Order Count: " . $count . "<br>";

$stmt = $pdo->query("SELECT * FROM orders");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Orders:<pre>";
print_r($orders);
echo "</pre>";
?>