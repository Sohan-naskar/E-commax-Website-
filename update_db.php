<?php
// update_db.php
include 'config/database.php';

try {
    // Add password column if it doesn't exist
    $sql = "ALTER TABLE customers ADD COLUMN password VARCHAR(255) NOT NULL AFTER email";
    $pdo->exec($sql);
    echo "Password column added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Password column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>