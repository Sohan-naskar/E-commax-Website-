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

    $user_id = $_SESSION['user_id'];
    $checkout_data = $_SESSION['checkout_data'];
    $payment_method = $_POST['payment_method'] ?? 'cod'; // Default to COD if not set

    // Determine Status
    // Determine Status: UPI/GPay/Paytm -> 'paid', COD -> 'unpaid'
    $status = 'unpaid'; // Default to Unpaid for COD (matches DB ENUM)
    if (in_array($payment_method, ['paytm', 'gpay', 'upi_id'])) {
        $status = 'paid';
    }

    // Insert Order
    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status, shipping_address, shipping_name, shipping_phone, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    // Note: Added payment_method column if it exists, or just store it. 
    // Checking schema from previous knowledge or assuming standard fields. 
    // If payment_method column doesn't exist, we might need to add it or just ignore.
    // For now, let's stick to the core request: "mark as unpaid/paid".
    // I will use specific column if available, otherwise just status.
    // Let's assume schema matches previous insert: (customer_id, total_amount, status, shipping_address, shipping_name, shipping_phone)
    // I will stick to the existing columns to avoid errors, unless I check schema.

    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status, shipping_address, shipping_name, shipping_phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $checkout_data['total_amount'],
        $status,
        $checkout_data['shipping_address'],
        $checkout_data['shipping_name'],
        $checkout_data['shipping_phone']
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert Order Items
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $stmt_item->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
    }

    // Clear Cart and Checkout Data
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