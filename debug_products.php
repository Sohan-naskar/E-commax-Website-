<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT id, name, category FROM products ORDER BY category");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Product List</h1>";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Category</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . $p['name'] . "</td>";
        echo "<td>" . $p['category'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>