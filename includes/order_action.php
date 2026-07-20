<?php
// includes/order_action.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Statuses where cancellation is NOT allowed
$non_cancellable = ['shipped', 'out_for_delivery', 'delivered', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    try {

        // ── Cancel Single Item ────────────────────────────────────────────────
        if ($action === 'cancel_item') {
            $order_id = intval($_POST['order_id'] ?? 0);
            $item_id  = intval($_POST['item_id']  ?? 0);

            // Verify order belongs to this user
            $stmt = $pdo->prepare("SELECT status, payment_status FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            $order_status = strtolower(trim($order['status']));

            // Block cancel if order is already dispatched
            if (in_array($order_status, $non_cancellable)) {
                $friendly = ucwords(str_replace('_', ' ', $order_status));
                echo json_encode(['success' => false, 'message' => "Cannot cancel — order is already {$friendly}."]);
                exit;
            }

            // Verify item belongs to this order
            $itemStmt = $pdo->prepare("SELECT id, status FROM order_items WHERE id = ? AND order_id = ?");
            $itemStmt->execute([$item_id, $order_id]);
            $item = $itemStmt->fetch();

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit;
            }

            if (strtolower($item['status']) === 'cancelled') {
                echo json_encode(['success' => false, 'message' => 'Item is already cancelled']);
                exit;
            }

            // Cancel the item
            $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE id = ?")
                ->execute([$item_id]);

            // Check if ALL remaining items are now cancelled
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND status != 'cancelled'");
            $stmtCheck->execute([$order_id]);
            $remaining = $stmtCheck->fetchColumn();

            if ($remaining == 0) {
                // All items cancelled → cancel the whole order
                $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")
                    ->execute([$order_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Item cancelled successfully']);
            exit;


        // ── Cancel Entire Order ───────────────────────────────────────────────
        } elseif ($action === 'cancel') {
            $order_id = intval($_POST['order_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            $order_status = strtolower(trim($order['status']));

            if (in_array($order_status, $non_cancellable)) {
                $friendly = ucwords(str_replace('_', ' ', $order_status));
                echo json_encode(['success' => false, 'message' => "Cannot cancel — order is already {$friendly}."]);
                exit;
            }

            $pdo->beginTransaction();

            // Cancel order and all its non-cancelled items
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")
                ->execute([$order_id]);
            $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ? AND status != 'cancelled'")
                ->execute([$order_id]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>