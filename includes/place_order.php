<?php
// includes/place_order.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart']) || empty($_SESSION['checkout_data'])) {
    header("Location: ../cart.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $user_id        = $_SESSION['user_id'];
    $checkout_data  = $_SESSION['checkout_data'];
    $payment_method = $_POST['payment_method'] ?? 'cod';

    // ── Payment Status ──────────────────────────────────────────────────────
    // Online payments (Paytm / GPay / UPI) → paid immediately
    // Cash on Delivery → unpaid until admin confirms
    $online_methods = ['paytm', 'gpay', 'upi_id', 'online'];
    $is_online      = in_array($payment_method, $online_methods);

    $payment_status = $is_online ? 'paid'   : 'unpaid';
    $order_status   = $is_online ? 'paid'   : 'pending'; // 'pending' = COD placed, not yet confirmed

    // ── Insert Order ────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO orders 
            (customer_id, total_amount, status, payment_status, payment_method,
             shipping_address, shipping_name, shipping_phone)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $checkout_data['total_amount'],
        $order_status,
        $payment_status,
        $payment_method,
        $checkout_data['shipping_address'] ?? '',
        $checkout_data['shipping_name']    ?? '',
        $checkout_data['shipping_phone']   ?? '',
    ]);

    $order_id = $pdo->lastInsertId();

    // ── Insert Order Items ──────────────────────────────────────────────────
    $stmt_item = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    foreach ($_SESSION['cart'] as $item) {
        $stmt_item->execute([
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
        ]);
    }

    // ── Clear Session ───────────────────────────────────────────────────────
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_data']);

    $pdo->commit();

    header("Location: ../order_success.php?id=" . $order_id);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error placing order: " . $e->getMessage());
}
?>