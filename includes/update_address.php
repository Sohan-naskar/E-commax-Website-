<?php
// includes/update_address.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $pincode = $_POST['pincode'];
    $locality = $_POST['locality'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $landmark = $_POST['landmark'];
    $alt_phone = $_POST['alternate_phone'];

    // Address Type is optional/not in form, default to Home? Or can add if needed.
    // For now we just update fields

    try {
        $update_sql = "UPDATE customers SET 
            name = ?, phone = ?, pincode = ?, locality = ?, address = ?, city = ?, state = ?, 
            landmark = ?, alternate_phone = ?
            WHERE id = ?";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$name, $phone, $pincode, $locality, $address, $city, $state, $landmark, $alt_phone, $user_id]);

        header("Location: ../profile.php?tab=addresses&updated=1");
        exit();
    } catch (PDOException $e) {
        die("Error updating address: " . $e->getMessage());
    }
} else {
    header("Location: ../profile.php?tab=addresses");
    exit();
}
?>