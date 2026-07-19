<?php
// includes/edit_address_profile.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_id = $_POST['address_id'];

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
        // Verify ownership and update
        $sql = "UPDATE customer_addresses SET 
                name = ?, phone = ?, pincode = ?, address = ?, city = ?, state = ?, 
                landmark = ?, alternate_phone = ?, address_type = ? 
                WHERE id = ? AND customer_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $phone, $pincode, $address, $city, $state, $landmark, $alt_phone, $address_type, $address_id, $user_id]);

        header("Location: ../profile.php?tab=addresses");
        exit();

    } catch (PDOException $e) {
        die("Error updating address: " . $e->getMessage());
    }
} else {
    header("Location: ../profile.php?tab=addresses");
    exit();
}
?>