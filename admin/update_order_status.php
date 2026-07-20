<?php
// admin/update_order_status.php
// AJAX endpoint — admin only
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id  = intval($_POST['order_id']  ?? 0);
$new_status = trim($_POST['new_status'] ?? '');

$allowed_statuses = ['pending', 'paid', 'unpaid', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refund_processing'];

if (!$order_id || !in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ── 1. Determine what to write to DB ────────────────────────────────────
    $db_status      = $new_status;       // what goes into orders.status
    $db_pay_status  = null;              // what goes into orders.payment_status (null = don't force change)
    $set_shipped_at   = false;
    $set_delivered_at = false;

    switch ($new_status) {
        case 'pending':
            $db_status     = 'pending';
            $db_pay_status = 'unpaid';
            break;

        case 'paid':
            $db_status     = 'paid';
            $db_pay_status = 'paid';
            break;

        case 'unpaid':
            $db_status     = 'unpaid';
            $db_pay_status = 'unpaid';
            break;

        case 'shipped':
            $db_status      = 'shipped';
            $db_pay_status  = 'paid';    // If shipped → assume payment verified
            $set_shipped_at = true;
            break;

        case 'out_for_delivery':
            $db_status     = 'out_for_delivery';
            $db_pay_status = 'paid';
            break;

        case 'delivered':
            $db_status        = 'delivered';
            $db_pay_status    = 'paid';
            $set_delivered_at = true;
            break;

        case 'cancelled':
            $db_status     = 'cancelled';
            // Don't change payment_status for plain cancel (stays as-is)
            break;

        case 'refund_processing':
            // Treat as cancelled + was paid
            $db_status     = 'cancelled';
            $db_pay_status = 'paid';
            break;
    }

    // ── 2. Build and run the UPDATE for orders ───────────────────────────────
    $setClauses = ['status = ?'];
    $params     = [$db_status];

    if ($db_pay_status !== null) {
        $setClauses[] = 'payment_status = ?';
        $params[]     = $db_pay_status;
    }
    if ($set_shipped_at) {
        $setClauses[] = 'shipped_at = NOW()';
    }
    if ($set_delivered_at) {
        $setClauses[] = 'delivered_at = NOW()';
    }

    $params[] = $order_id;
    $pdo->prepare("UPDATE orders SET " . implode(', ', $setClauses) . " WHERE id = ?")
        ->execute($params);

    // ── 3. Cascade to order_items ─────────────────────────────────────────────
    if (in_array($new_status, ['shipped', 'out_for_delivery', 'delivered'])) {
        // Sync all non-cancelled items to the new status
        $pdo->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND status != 'cancelled'")
            ->execute([$db_status, $order_id]);

    } elseif (in_array($new_status, ['cancelled', 'refund_processing'])) {
        // Cancel all items
        $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ? AND status != 'cancelled'")
            ->execute([$order_id]);
    }
    // pending / paid / unpaid — don't touch individual item statuses

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'message'    => 'Order status updated to: ' . ucwords(str_replace('_', ' ', $db_status)),
        'new_status' => $db_status,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>
