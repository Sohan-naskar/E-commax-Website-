<?php
// includes/wishlist_action.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'add' || $action === 'toggle') {
        $product_id = $_POST['product_id'];

        // Check if exists
        $check = $pdo->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);

        if ($check->rowCount() > 0) {
            // Item exists, so we remove it (Toggle behavior)
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
        } else {
            // Item does not exist, so we add it
            $stmt = $pdo->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
        }

    } elseif ($action === 'remove') {
        $wishlist_id = $_POST['wishlist_id'];

        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND customer_id = ?");
        $stmt->execute([$wishlist_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found or already removed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>