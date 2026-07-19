<?php
// setup_advanced_db.php
require_once 'config/database.php';

try {
    // 1. Create Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        category VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Products table created.<br>";

    // 2. Create Messages Table (Feedback)
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");
    echo "Messages table created.<br>";

    // 3. Create Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('paid', 'unpaid', 'cancelled') DEFAULT 'unpaid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");
    echo "Orders table created.<br>";

    // 4. Create Order Items Table (To link orders to products)
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "Order Items table created.<br>";

    // 5. Update Customers Table (Add last_activity)
    // Check if column exists first to avoid error
    $col_check = $pdo->query("SHOW COLUMNS FROM customers LIKE 'last_activity'");
    if ($col_check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN last_activity DATETIME NULL");
        echo "Customers table updated with last_activity column.<br>";
    } else {
        echo "Customers table already has last_activity column.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>