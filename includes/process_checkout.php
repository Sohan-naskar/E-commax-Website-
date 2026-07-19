<?php
// includes/process_checkout.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: ../cart.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $user_id = $_SESSION['user_id'];
    $total_amount = 0;

    // Calculate total
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Capture Address Data
    $address_id = isset($_POST['address_id']) ? $_POST['address_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // NEW: Handle Edit Address
    if ($action === 'edit_address' && $address_id) {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $pincode = $_POST['pincode'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $landmark = $_POST['landmark'];
        $alt_phone = $_POST['alternate_phone'];
        $address_type = $_POST['address_type'];

        // Update DB
        $stmt_update = $pdo->prepare("UPDATE customer_addresses SET name=?, phone=?, pincode=?, address=?, city=?, state=?, landmark=?, alternate_phone=?, address_type=? WHERE id=? AND customer_id=?");
        $stmt_update->execute([$name, $phone, $pincode, $address, $city, $state, $landmark, $alt_phone, $address_type, $address_id, $user_id]);

        // After update, fall through to $address_id logic below (Scenario 1) which will fetch the updated data.
    }

    $new_address_action = ($action === 'new_address');

    if ($address_id && !$new_address_action) {
        // SCENARIO 1: Existing Address Selected (or just Edited)
        $stmt_addr = $pdo->prepare("SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ?");
        $stmt_addr->execute([$address_id, $user_id]);
        $addr = $stmt_addr->fetch();

        if (!$addr) {
            die("Invalid address selected.");
        }

        // Format Snapshot from DB
        $full_address = $addr['name'] . "\n" .
            $addr['phone'] . "\n" .
            $addr['address'] . "\n" .
            ($addr['landmark'] ? "Near " . $addr['landmark'] . "\n" : "") .
            $addr['city'] . ", " . $addr['state'] . " - " . $addr['pincode'];

        $shipping_name = $addr['name'];
        $shipping_phone = $addr['phone'];

    } elseif ($new_address_action) {
        // SCENARIO 2: New Address Added
        $name = $_POST['name'];
        $phone = $_POST['phone'];

        // SERVER-SIDE VALIDATION: 10 Digits
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            die("<div style='text-align:center; padding:50px;'><h3>Error: Phone number must be exactly 10 digits.</h3><br><a href='../cart.php'>Go Back</a></div>");
        }

        $pincode = $_POST['pincode'];
        // $locality = $_POST['locality']; // Removed from form
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $landmark = $_POST['landmark'];
        $alt_phone = $_POST['alternate_phone'];
        $address_type = $_POST['address_type'];

        // Insert into customer_addresses (Save it!)
        $stmt = $pdo->prepare("INSERT INTO customer_addresses (customer_id, name, phone, pincode, address, city, state, landmark, alternate_phone, address_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $phone, $pincode, $address, $city, $state, $landmark, $alt_phone, $address_type]);

        // Format Snapshot from Input
        $full_address = "$name\n$phone\n$address\n";
        if ($landmark)
            $full_address .= "Near $landmark\n";
        $full_address .= "$city, $state - $pincode";

        $shipping_name = $name;
        $shipping_phone = $phone;

    } else {
        die("Please select or add an address.");
    }

    // Store Checkout Data in Session for Payment Page
    $_SESSION['checkout_data'] = [
        'total_amount' => $total_amount,
        'shipping_address' => $full_address,
        'shipping_name' => $shipping_name,
        'shipping_phone' => $shipping_phone
    ];

    $pdo->commit();

    header("Location: ../payment.php");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error processing order: " . $e->getMessage());
}
?>