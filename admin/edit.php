<?php
// admin/edit.php
include '../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found!");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $pincode = $_POST['pincode'];
    $locality = $_POST['locality'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $landmark = $_POST['landmark'];
    $alternate_phone = $_POST['alternate_phone'];
    $address_type = $_POST['address_type'];

    try {
        $sql = "UPDATE customers SET name=?, email=?, phone=?, address=?, pincode=?, locality=?, city=?, state=?, landmark=?, alternate_phone=?, address_type=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $address, $pincode, $locality, $city, $state, $landmark, $alternate_phone, $address_type, $id]);
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edit Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Edit Customer</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($customer['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($customer['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    value="<?= htmlspecialchars($customer['phone']) ?>">
                            </div>
                            <div class="mb-3">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control"
                                    value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Locality</label>
                                <input type="text" name="locality" class="form-control"
                                    value="<?= htmlspecialchars($customer['locality'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Address (Area and Street)</label>
                                <textarea name="address"
                                    class="form-control"><?= htmlspecialchars($customer['address']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label>City/District/Town</label>
                                <input type="text" name="city" class="form-control"
                                    value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>State</label>
                                <input type="text" name="state" class="form-control"
                                    value="<?= htmlspecialchars($customer['state'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Landmark</label>
                                <input type="text" name="landmark" class="form-control"
                                    value="<?= htmlspecialchars($customer['landmark'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Alternate Phone</label>
                                <input type="text" name="alternate_phone" class="form-control"
                                    value="<?= htmlspecialchars($customer['alternate_phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Address Type</label>
                                <select name="address_type" class="form-control">
                                    <option value="Home" <?= ($customer['address_type'] ?? '') == 'Home' ? 'selected' : '' ?>>Home</option>
                                    <option value="Work" <?= ($customer['address_type'] ?? '') == 'Work' ? 'selected' : '' ?>>Work</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Customer</button>
                            <a href="index.php" class="btn btn-link w-100 mt-2">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>