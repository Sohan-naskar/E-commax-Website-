<?php
require_once '../config/database.php';

if (isset($_GET['term'])) {
    $term = trim($_GET['term']);

    // Return JSON
    header('Content-Type: application/json');

    if (strlen($term) > 0) {
        try {
            // Search for matches in product name
            // Limit to 5 results for dropdown
            // Search for matches in product name, description, or category
            // Limit to 5 results for dropdown
            $stmt = $pdo->prepare("
                SELECT id, name, image, price, category
                FROM products
                WHERE name LIKE ? OR category LIKE ?
                LIMIT 5
            ");
            $likeTerm = "%" . $term . "%";
            $stmt->execute([$likeTerm, $likeTerm]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($results);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode([]);
    }
}
?>