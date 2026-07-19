<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    try {
        if ($action === 'cancel_item') {
            $order_id = $_POST['order_id'];
            $item_id = $_POST['item_id'];

            // Verify Order and ownership
            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            // Cannot cancel if already shipped, delivered, or fully cancelled
            if (in_array(strtolower($order['status']), ['shipped', 'delivered', 'cancelled'])) {
                echo json_encode(['success' => false, 'message' => 'Cannot cancel item as the order is already ' . $order['status']]);
                exit;
            }

            // Verify Item belongs to order
            $itemStmt = $pdo->prepare("SELECT id, status FROM order_items WHERE id = ? AND order_id = ?");
            $itemStmt->execute([$item_id, $order_id]);
            $item = $itemStmt->fetch();

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit;
            }

            if ($item['status'] === 'cancelled') {
                echo json_encode(['success' => false, 'message' => 'Item already cancelled']);
                exit;
            }

            // Update item status
            $updateStmt = $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE id = ?");
            $updateStmt->execute([$item_id]);

            // Check if all items are cancelled? If so, cancel order.
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND status != 'cancelled'");
            $stmtCheck->execute([$order_id]);
            $remainingItems = $stmtCheck->fetchColumn();

            if ($remainingItems == 0) {
                // All items cancelled, update order status
                $updateOrderStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $updateOrderStmt->execute([$order_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Item cancelled successfully']);
            exit;

        } elseif ($action === 'cancel') {
            // Legacy Order Cancel support
            $order_id = $_POST['order_id'];

            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            if (in_array(strtolower($order['status']), ['delivered', 'cancelled', 'shipped'])) {
                echo json_encode(['success' => false, 'message' => 'Cannot cancel this order']);
                exit;
            }

            $updateStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $updateStmt->execute([$order_id]);

            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>