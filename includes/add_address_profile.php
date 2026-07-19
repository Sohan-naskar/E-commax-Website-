<?php
// includes/add_address_profile.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Capture fields
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $pincode = trim($_POST['pincode']);
    // $locality = trim($_POST['locality']); // Removed
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $landmark = trim($_POST['landmark']);
    $alt_phone = trim($_POST['alternate_phone']);
    $address_type = isset($_POST['address_type']) ? $_POST['address_type'] : 'Home';

    try {
        $stmt = $pdo->prepare("INSERT INTO customer_addresses (customer_id, name, phone, pincode, address, city, state, landmark, alternate_phone, address_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $phone, $pincode, $address, $city, $state, $landmark, $alt_phone, $address_type]);

        header("Location: ../profile.php?tab=addresses");
        exit();

    } catch (PDOException $e) {
        die("Error adding address: " . $e->getMessage());
    }
} else {
    header("Location: ../profile.php?tab=addresses");
    exit();
}
?>