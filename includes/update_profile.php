<?php
// includes/update_profile.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$updated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Update Personal Info (Name & Gender)
    if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
        $full_name = trim($_POST['first_name'] . ' ' . $_POST['last_name']);
        // Assuming we might have a gender column later, but for now just Name
        // If DB has gender, update it too. The view has gender radios.
        // Let's check DB schema or just assume name for now.
        // The user view has Gender. I should probably add it to DB if relevant, or just ignore if not in DB.
        // Current customers table: id, name, email, phone, address, + new address fields.
        // No gender column in schema I saw earlier. I will just update Name.

        $stmt = $pdo->prepare("UPDATE customers SET name = ? WHERE id = ?");
        $stmt->execute([$full_name, $user_id]);
        $updated = true;
    }

    // 2. Update Email
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() == 0) {
            $stmt = $pdo->prepare("UPDATE customers SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);
            $updated = true;
        } else {
            // Email taken - handle error? For simple flow, just redirect with error.
            header("Location: ../profile.php?tab=profile&error=email_taken");
            exit();
        }
    }

    // 3. Update Phone
    if (isset($_POST['phone'])) {
        $phone = trim($_POST['phone']);
        $stmt = $pdo->prepare("UPDATE customers SET phone = ? WHERE id = ?");
        $stmt->execute([$phone, $user_id]);
        $updated = true;
    }

    if ($updated) {
        header("Location: ../profile.php?tab=profile&success=1");
        exit();
    }
}

header("Location: ../profile.php?tab=profile");
exit();
?>