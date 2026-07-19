<?php
// migrate_products.php
require_once 'config/database.php';
require_once 'includes/products_data.php'; // Load the array $products

try {
    // Check if products table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() > 0) {
        echo "Products table is not empty. Skipping migration.<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image, category) VALUES (?, ?, ?, ?, ?)");

        foreach ($products as $p) {
            // Determine category based on name/logic (Simple auto-categorization)
            $category = 'Electronics';
            if (stripos($p['name'], 'Watch') !== false)
                $category = 'Smartwatch';
            elseif (stripos($p['name'], 'Speaker') !== false)
                $category = 'Audio';
            elseif (stripos($p['name'], 'Headphone') !== false)
                $category = 'Audio';
            elseif (stripos($p['name'], 'Earbud') !== false)
                $category = 'Audio';
            elseif (stripos($p['name'], 'Camera') !== false)
                $category = 'Camera';
            elseif (stripos($p['name'], 'Phone') !== false)
                $category = 'Smartphone';
            elseif (stripos($p['name'], 'Book') !== false)
                $category = 'Laptop';
            elseif (stripos($p['name'], 'Power') !== false)
                $category = 'Accessory';

            $stmt->execute([
                $p['name'],
                $p['price'],
                $p['description'],
                $p['image'],
                $category
            ]);
            echo "Migrated: " . htmlspecialchars($p['name']) . "<br>";
        }
        echo "Migration completed successfully.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>