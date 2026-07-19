<?php
// includes/cart_action.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';

// Add to Cart (Secure Lookup)
if (($action == 'add' || $action == 'add_ajax') && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Fetch product from DB to ensuring valid ID and Price
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Check if product already exists in cart
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => 1
            ];
        }
    }

    if ($action == 'add_ajax') {
        echo json_encode([
            'success' => true,
            'cart_count' => count($_SESSION['cart']),
            'message' => 'Item added to cart'
        ]);
        session_write_close();
        exit();
    }

    // Redirect back for standard requests
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Remove from Cart
if ($action == 'remove' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header('Location: ../cart.php');
    exit();
}

// Clear Cart
if ($action == 'clear') {
    $_SESSION['cart'] = [];
    header('Location: ../cart.php');
    exit();
}

header('Location: ../index.php');
?>