<?php
include 'auth_check.php';
require_once '../config/database.php';

// Handle Add Product Logic
$product_msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $category = $_POST['category'];

    // Default image
    $image = 'assets/images/prod_watch_1770063262605.png';

    // Handle File Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        require_once '../includes/ImageKitUploader.php';

        $uploader = new ImageKitUploader();
        $uploadResult = $uploader->upload($_FILES['image']['tmp_name'], $_FILES['image']['name']);

        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $product_msg = "ImageKit Upload Failed: " . $uploadResult['error'];
        }
    }

    if (empty($product_msg) || $product_msg == "Product added successfully!") {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $desc, $image, $category]);

            // Redirect to prevent form resubmission and keep user on Products tab
            header("Location: index.php?product_added=1");
            exit();
        } catch (PDOException $e) {
            $product_msg = "Error: " . $e->getMessage();
        }
    }
}

// Handle Update Product Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $category = $_POST['category'];

    // Keep existing image by default
    $image = $_POST['existing_image'];

    // Handle New Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        require_once '../includes/ImageKitUploader.php';
        $uploader = new ImageKitUploader();
        $uploadResult = $uploader->upload($_FILES['image']['tmp_name'], $_FILES['image']['name']);

        if ($uploadResult['success']) {
            $image = $uploadResult['url'];
        } else {
            $product_msg = "Image Update Failed: " . $uploadResult['error'];
        }
    }

    if (empty($product_msg)) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, description=?, category=?, image=? WHERE id=?");
            $stmt->execute([$name, $price, $desc, $category, $image, $id]);
            header("Location: index.php?product_updated=1");
            exit();
        } catch (PDOException $e) {
            $product_msg = "Error updating product: " . $e->getMessage();
        }
    }
}

// Handle Update Customer Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_customer'])) {
    $cust_id = $_POST['cust_id'];
    $name = $_POST['cust_name'];
    $email = $_POST['cust_email'];
    $phone = $_POST['cust_phone'];
    $address = $_POST['cust_address'];
    $locality = $_POST['cust_locality'];
    $city = $_POST['cust_city'];
    $state = $_POST['cust_state'];
    $pincode = $_POST['cust_pincode'];

    try {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, locality=?, city=?, state=?, pincode=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $address, $locality, $city, $state, $pincode, $cust_id]);
        header("Location: index.php?customer_updated=1");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating customer: " . $e->getMessage();
    }
}
// Handle Update Order Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    try {
        $pdo->beginTransaction();

        // 1. Update main order status
        // Handle "refund_processing" -> Status: Cancelled, Payment: Paid
        if ($new_status == 'refund_processing') {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$order_id]);
            $is_cancelled_action = true; // Flag for item update
        }
        // Handle "cancelled" -> Status: Cancelled, Payment: Unpaid
        elseif ($new_status == 'cancelled') {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
            $stmt->execute([$order_id]);
            $is_cancelled_action = true; // Flag for item update
        }
        // Handle Standard Statuses
        else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $is_cancelled_action = false;
        }

        // 2. Sync Payment Status: If shipped/delivered/paid, it must be 'paid'
        if (in_array($new_status, ['shipped', 'delivered', 'paid'])) {
            $stmtPay = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmtPay->execute([$order_id]);

            // Sync Items: Update all non-cancelled items to match order status
            // This allows the "Quick Update" from the table to work as a bulk action
            $stmtItems = $pdo->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND status != 'cancelled'");
            $stmtItems->execute([$new_status, $order_id]);
        }

        // 3. Sync Item Status: If order is cancelled (either via 'cancelled' or 'refund_processing'), all items are cancelled
        if (isset($is_cancelled_action) && $is_cancelled_action) {
            $stmtItems = $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ? AND status != 'cancelled'");
            $stmtItems->execute([$order_id]);
        }

        $pdo->commit();

        // Preserve filter/view params
        $redirect_url = "index.php?order_updated=1";
        if (isset($_GET['status']))
            $redirect_url .= "&status=" . $_GET['status'];
        if (isset($_GET['view']))
            $redirect_url .= "&view=" . $_GET['view'];

        header("Location: " . $redirect_url);
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error updating order status: " . $e->getMessage();
    }
}

// Handle Delete Order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];

    try {
        $pdo->beginTransaction();

        // 1. Delete Order Items first (Foreign Key constraint usually requires this, though cascade might handle it)
        $stmtItems = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order_id]);

        // 2. Delete Order
        $stmtOrder = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmtOrder->execute([$order_id]);

        // 3. AUTO-RESET Logic: If table is empty, reset IDs to 1
        $countStmt = $pdo->query("SELECT COUNT(*) FROM orders");
        if ($countStmt->fetchColumn() == 0) {
            // Disable foreign keys temporarily
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE order_items");
            $pdo->exec("TRUNCATE TABLE orders");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }

        $pdo->commit();
        header("Location: index.php?order_deleted=1");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error deleting order: " . $e->getMessage();
    }
}

// Handle Update Item Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item_status'])) {
    $item_id = $_POST['item_id'];
    $new_item_status = $_POST['new_item_status'];
    $order_id = $_POST['order_id'];

    try {
        $pdo->beginTransaction();

        // 1. Update Item Status
        if ($new_item_status == 'refund_item') {
            // "Refund": Set Item Status = 'cancelled'
            $stmt = $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$item_id]);

            // AND ensure Order Payment Status = 'paid' for refund logic
            $stmtOrder = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmtOrder->execute([$order_id]);
        } else {
            // Standard update (paid, shipped, delivered, cancelled, pending)
            $stmt = $pdo->prepare("UPDATE order_items SET status = ? WHERE id = ?");
            $stmt->execute([$new_item_status, $item_id]);
        }

        // 2. Check Order Logic
        // Get all item statuses for this order
        $stmtAll = $pdo->prepare("SELECT status FROM order_items WHERE order_id = ?");
        $stmtAll->execute([$order_id]);
        $all_items = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

        $all_cancelled = true;
        $all_delivered = true;
        // Count ACTIVE (non-cancelled) items
        $active_item_count = 0;
        $active_shipped_or_delivered_count = 0;
        $any_shipped_delivered = false;

        foreach ($all_items as $status) {
            if ($status != 'cancelled') {
                $all_cancelled = false;
                $active_item_count++;

                if ($status == 'delivered' || $status == 'shipped') {
                    $active_shipped_or_delivered_count++;
                    $any_shipped_delivered = true;
                }
            }

            // Strictly check for delivery of ALL non-cancelled items
            if ($status != 'delivered' && $status != 'cancelled') {
                $all_delivered = false;
            }
        }

        // Auto-Update Main Order Status
        if ($all_cancelled) {
            $stmtOrder = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmtOrder->execute([$order_id]);
        } elseif ($all_delivered && $active_item_count > 0) {
            // If all active items are DELIVERED
            $stmtOrder = $pdo->prepare("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?");
            $stmtOrder->execute([$order_id]);
        } elseif ($active_shipped_or_delivered_count == $active_item_count && $active_item_count > 0) {
            // If all active items are SHIPPED (or Delivered) -> Order is SHIPPED
            $stmtOrder = $pdo->prepare("UPDATE orders SET status = 'shipped', payment_status = 'paid' WHERE id = ?");
            $stmtOrder->execute([$order_id]);
        } elseif ($any_shipped_delivered) {
            // Partial shipment or just started -> Ensure Paid
            $stmtOrder = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmtOrder->execute([$order_id]);
        }

        $pdo->commit();
        $redirect_url = "index.php?order_updated=1";
        if (isset($_GET['status']))
            $redirect_url .= "&status=" . $_GET['status'];
        if (isset($_GET['view']))
            $redirect_url .= "&view=" . $_GET['view'];

        header("Location: " . $redirect_url);
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error updating item status: " . $e->getMessage();
    }
}

// 1. Fetch Stats
// Total Orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $stmt->fetchColumn();

// Unpaid Orders
// Status is 'unpaid' AND NOT (all items cancelled) (Partial cancel still unpaid or partially paid? usually unpaid is whole or nothing. Let's keep logic simple: unpaid + items active).
// Actually, if an order is unpaid and partial cancel, it's still unpaid.
$stmt = $pdo->query("
    SELECT COUNT(*) FROM orders o 
    WHERE (o.status = 'unpaid' OR o.status = 'pending')
    AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status != 'cancelled')
");
$unpaid_orders = $stmt->fetchColumn();

// Paid Orders
// Status is 'paid', 'shipped', or 'delivered' AND (at least ONE item is NOT cancelled)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM orders o 
    WHERE o.status IN ('paid', 'shipped', 'delivered') 
    AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status != 'cancelled')
");
$paid_orders = $stmt->fetchColumn();

// Cancelled Orders
// Status is 'cancelled' OR (ANY item cancelled) - include mixed orders
$stmt = $pdo->query("
    SELECT COUNT(*) FROM orders o 
    WHERE o.status = 'cancelled' 
    OR EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status = 'cancelled')
");
$cancelled_orders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$total_customers = $stmt->fetchColumn();


// 2. Fetch Data for Tables
// Filter by Status
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$where_sql = "";
$params = [];

if ($filter_status == 'unpaid') {
    $where_sql = "WHERE (o.status = 'unpaid' OR o.status = 'pending')";
} elseif ($filter_status == 'paid') {
    // Show only fully paid, shipped, or delivered (exclude fully cancelled)
    $where_sql = "WHERE o.status IN ('paid', 'shipped', 'delivered') AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status != 'cancelled')";
} elseif ($filter_status == 'cancelled') {
    // Show orders that are fully cancelled OR have ANY cancelled items
    $where_sql = "WHERE (o.status = 'cancelled' OR EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.status = 'cancelled'))";
}

// Recent Orders (Ungrouped - Individual Orders)
$pdo->exec("SET SESSION group_concat_max_len = 100000"); // Increase limit to prevent truncation
$sql = "
    SELECT o.id as order_id, o.customer_id, o.total_amount, TRIM(o.status) as status, o.created_at, TRIM(o.payment_status) as payment_status, o.shipping_address, o.shipping_name, o.shipping_phone,
    c.name as customer_name, c.email as customer_email,
    GROUP_CONCAT(CONCAT(p.name, '::', oi.quantity, '::', p.image, '::', p.price, '::', REPLACE(REPLACE(COALESCE(p.description, 'No description'), '::', ' '), '||', ' '), '::', p.category, '::', oi.status, '::', oi.id) SEPARATOR '||') as product_details
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    $where_sql
    GROUP BY o.id
    ORDER BY o.created_at DESC LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Feedback
$stmt = $pdo->query("SELECT m.*, c.name as customer_name FROM messages m JOIN customers c ON m.customer_id = c.id ORDER BY m.created_at DESC LIMIT 10");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Products
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
if ($category_filter && $category_filter != 'all') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
    $stmt->execute([$category_filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customers
// Customers with Order Stats
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(o.id) as total_orders, 
           COALESCE(SUM(o.total_amount), 0) as total_spent 
    FROM customers c 
    LEFT JOIN orders o ON c.id = o.customer_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | eCommax</title>
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon-removebg-preview.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            /* Website-related background (Visible Tech Theme) */
            background: linear-gradient(rgba(255, 255, 255, 0.4), rgba(240, 244, 248, 0.6)),
                url('../assets/images/hero_headphones_1770063248097.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #333;
            /* Dark text for readability on light bg */
        }

        /* Glassmorphism Navbar */
        .navbar {
            background: #ffffff !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Tab Navigation */
        .nav-tabs {
            border-bottom: none;
        }

        .nav-link {
            color: #555;
            border: none;
            font-weight: 500;
            transition: all 0.3s;
            border-radius: 8px 8px 0 0 !important;
        }

        .nav-link:hover {
            color: #000;
            background: rgba(0, 0, 0, 0.05);
        }

        .nav-link.active {
            background: #fff !important;
            color: #4200FF !important;
            /* Brand accented active state */
            font-weight: 600;
            border: none;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05);
            /* Subtle lift */
        }

        /* Glass Cards - General */
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            /* Softer shadow */
            border-radius: 16px;
            transition: transform 0.2s;
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: #333;
        }

        /* Stats Cards - Glossy & Vibrant (To stand out on light bg) */
        .card.bg-primary,
        .card.bg-warning,
        .card.bg-success,
        .card.bg-info {
            border: none !important;
            color: #fff !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            border-left: none !important;
            /* Remove side border, stick to full fill */
        }

        /* Vibrant Gradients for Stats to Pop */
        .card.bg-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
        }

        .card.bg-warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%) !important;
        }

        .card.bg-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
        }

        .card.bg-info {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%) !important;
        }

        .card-title {
            opacity: 0.9;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .display-6 {
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }


        /* Keep colored accents for identification */
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        }

        .display-6 {
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* General Card Reset for Content (White Glass) */
        .card {
            color: #333;
            /* Reset text to dark for general cards */
        }

        .card-body {
            color: #333;
            /* Reset body text */
        }

        /* Section Headers - Revert to Vibrant Gradients */
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            border-radius: 16px 16px 0 0 !important;
        }

        #orders .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border-top: none;
        }

        #feedback .card-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
            color: white !important;
            border-top: none;
        }

        #products .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
            color: white !important;
            border-top: none;
        }

        #customers .card-header {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
            color: #1a1a1a !important;
            border-top: none;
        }

        #admins .card-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
            color: white !important;
            border-top: none;
        }

        .table {
            color: #333;
        }

        .table thead th {
            color: #666;
            border-bottom: 2px solid #eee;
        }

        .table td {
            border-bottom: 1px solid #eee;
        }

        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/images/logo.svg" alt="eCommax Admin" height="60">
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-dark"
                        id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-light d-flex justify-content-center align-items-center border"
                            style="width: 40px; height: 40px;">
                            <i class="fas fa-user-shield text-dark fs-5"></i>
                        </div>
                        <span
                            class="text-dark d-none d-md-block fw-bold"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="create_admin.php">Add Another Admin</a></li>
                    </ul>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <!-- STATS ROW -->
        <!-- STATS ROW (Clickable) -->
        <div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
            <div class="col">
                <a href="index.php?view=orders" class="text-decoration-none">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Orders</h5>
                            <h2 class="display-6 fw-bold"><?= $total_orders ?></h2>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?status=unpaid" class="text-decoration-none">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <h5 class="card-title">Unpaid Orders</h5>
                            <h2 class="display-6 fw-bold"><?= $unpaid_orders ?></h2>
                        </div>
                    </div>
                </a>
            </div>
            <div class=" col">
                <a href="index.php?status=paid" class="text-decoration-none">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Paid Orders</h5>
                            <h2 class="display-6 fw-bold"><?= $paid_orders ?></h2>
                        </div>
                    </div>
                </a>
            </div>
            <div class=" col">
                <div class="card bg-info text-white h-100" style="cursor: pointer;" onclick="showCustomersTab()">
                    <div class="card-body">
                        <h5 class="card-title">Total Customers</h5>
                        <h2 class="display-6 fw-bold"><?= $total_customers ?></h2>
                        <small class="text-white-50">Registered Users</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS & TOASTS WRAPPER -->
        <div class="position-relative">
            <!-- Toast Container (Now Relative to Tabs Area) -->
            <div class="toast-container position-absolute top-0 end-0 p-3" style="z-index: 1050; margin-top: -10px;">
                <?php if (isset($_GET['order_updated'])): ?>
                    <div id="liveToastUpdate" class="toast align-items-center text-bg-success border-0 shadow" role="alert"
                        aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i> Order updated successfully!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var toast = new bootstrap.Toast(document.getElementById('liveToastUpdate'));
                            toast.show();
                            // Clean URL so refresh doesn't show toast again
                            const url = new URL(window.location);
                            url.searchParams.delete('order_updated');
                            window.history.replaceState({}, '', url);
                        });
                    </script>
                <?php endif; ?>

                <?php if (isset($_GET['order_deleted'])): ?>
                    <div id="liveToastDelete" class="toast align-items-center text-bg-danger border-0 shadow" role="alert"
                        aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-trash-alt me-2"></i> Order has been permanently deleted.
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var toast = new bootstrap.Toast(document.getElementById('liveToastDelete'));
                            toast.show();
                            // Clean URL so refresh doesn't show toast again
                            const url = new URL(window.location);
                            url.searchParams.delete('order_deleted');
                            window.history.replaceState({}, '', url);
                        });
                    </script>
                <?php endif; ?>
            </div>
            <ul class="nav nav-tabs mb-4" id="adminTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders"
                        type="button" role="tab">Orders</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products"
                        type="button" role="tab">Products</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers"
                        type="button" role="tab">Customers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback"
                        type="button" role="tab">Feedback</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button"
                        role="tab">Admins</button>
                </li>
            </ul>

            <div class="tab-content" id="adminTabContent">

                <!-- ORDERS TAB -->
                <div class="tab-pane fade show active" id="orders" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">Recent Orders
                        </div>
                        <div class="card-body">
                            <?php if (isset($_GET['order_updated']) || isset($_GET['order_deleted'])): ?>
                                <!-- Toast Logic Handled in Footer -->
                            <?php endif; ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Customer</th>
                                        <th>Products</th>
                                        <th>Total (₹)</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1;
                                    foreach ($orders as $o):
                                        // Infer "Cancelled" status if all items are cancelled
                                        $all_merged_items_cancelled = true;
                                        $parsed_products = [];

                                        if ($o['product_details']) {
                                            $current_view_status = isset($_GET['status']) ? trim($_GET['status']) : '';
                                            $raw_prods = explode('||', $o['product_details']);
                                            foreach ($raw_prods as $rp) {
                                                $details = explode('::', $rp);
                                                // 0:Name, 1:Qty, 2:Img, 3:Price, 4:Desc, 5:Cat, 6:Status, 7:ItemId
                                                $item_stat = isset($details[6]) ? $details[6] : 'pending';
                                                $item_id_val = isset($details[7]) ? $details[7] : '';
                                                $clean_item_stat = strtolower(trim($item_stat));

                                                if ($clean_item_stat != 'cancelled') {
                                                    $all_merged_items_cancelled = false;
                                                }
                                                // FILTER LOGIC:
                                                // Only apply item filters if the order is NOT fully cancelled.
                                                // If the whole order is cancelled, we want to see everything.
                                                if ($o['status'] != 'cancelled') {
                                                    // If view is refund AND item is NOT cancelled, SKIP IT.
                                                    if ($current_view_status == 'refund' && $clean_item_stat != 'cancelled') {
                                                        continue;
                                                    }

                                                    // If view is paid AND item IS cancelled, SKIP IT (show only paid/active items).
                                                    if ($current_view_status == 'paid' && $clean_item_stat == 'cancelled') {
                                                        continue;
                                                    }

                                                    // If view is CANCELLED AND item is NOT cancelled, SKIP IT.
                                                    if ($current_view_status == 'cancelled' && $clean_item_stat != 'cancelled') {
                                                        continue;
                                                    }
                                                }

                                                $parsed_products[] = [
                                                    'name' => $details[0] ?? 'Unknown',
                                                    'qty' => $details[1] ?? 1,
                                                    'status' => $item_stat
                                                ];
                                            }
                                        } else {
                                            $all_merged_items_cancelled = false;
                                        }

                                        // Determine display status based on item states
                                        $has_cancelled_items = false;
                                        foreach ($parsed_products as $prod) {
                                            if (strtolower(trim($prod['status'])) == 'cancelled') {
                                                $has_cancelled_items = true;
                                            }
                                        }

                                        $display_status = $o['status'];

                                        // Logic for status badge
                                        if ($all_merged_items_cancelled && count($parsed_products) > 0) {
                                            $display_status = 'cancelled';
                                        } elseif ($has_cancelled_items && $o['payment_status'] == 'paid' && $o['status'] == 'paid') {
                                            // If we have cancelled items and it was paid AND main status is 'paid', show refund processing.
                                            // If status is Shipped/Delivered, show that instead.
                                            $display_status = 'partial_refund';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $sn++ ?></td>
                                            <td><?= htmlspecialchars($o['customer_name']) ?>
                                            </td>
                                            <td>
                                                <?php
                                                foreach ($parsed_products as $prod) {
                                                    $cancelled_badge = ($prod['status'] == 'cancelled') ? '<span class="text-danger small fw-bold ms-1">(Cancelled)</span>' : '';
                                                    echo '<div class="small text-muted mb-1">• ' . htmlspecialchars($prod['name']) . ' <span class="fw-bold">x' . htmlspecialchars($prod['qty']) . '</span>' . $cancelled_badge . '</div>';
                                                }
                                                ?>
                                            </td>
                                            <td>₹<?= number_format($o['total_amount'], 2) ?>
                                            </td>
                                            <td>
                                                <?php if ($o['status'] == 'paid' || $o['status'] == 'shipped' || $o['status'] == 'delivered'): ?>
                                                    <span class="badge bg-success" style="width: 100px;">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark" style="width: 100px;">Unpaid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, H:i', strtotime($o['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        onclick='viewOrder(<?= htmlspecialchars(json_encode($o), ENT_QUOTES, "UTF-8") ?>)'>View</button>

                                                    <button class="btn btn-sm btn-outline-danger" title="Delete Order"
                                                        onclick="confirmDeleteOrder(<?= $o['order_id'] ?>)">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                <!-- PRODUCTS TAB -->
                <div class="tab-pane fade" id="products" role="tabpanel">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold">Add New
                            Product</div>
                        <div class="card-body">
                            <?php if (isset($_GET['product_added'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Product added successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($product_msg): ?>
                                <div class="alert alert-danger"><?= $product_msg ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="row g-3">
                                <input type="hidden" name="add_product" value="1">
                                <div class="col-md-6">
                                    <label class="form-label">Product
                                        Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Price (₹)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select" required>
                                        <option value="" selected disabled>
                                            Select Category</option>
                                        <option value="smartphone">Mobile
                                        </option>
                                        <option value="watch">Watch</option>
                                        <option value="audio">Audio</option>
                                        <option value="laptop">Laptop</option>
                                        <option value="camera">Camera
                                        </option>
                                        <option value="home-appliance">Home
                                            Appliance</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="2"></textarea>
                                </div>
                                <!-- Moved Image Upload near Button -->
                                <div class="col-md-12">
                                    <label class="form-label">Product
                                        Image</label>
                                    <input type="file" name="image" class="form-control" accept="image/*" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Add
                                        Product</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            // 1. Determine active tab
                            // Priority: URL Param (Action just happened) > LocalStorage (User preference) > Default
                            const urlParams = new URLSearchParams(window.location.search);
                            let activeTabId = localStorage.getItem('activeAdminTab');

                            // Overrides for specific actions
                            if (urlParams.has('product_added') || urlParams.has('product_deleted')) {
                                activeTabId = 'products-tab';
                            } else if (urlParams.get('view') === 'customers') {
                                activeTabId = 'customers-tab';
                            } else if (urlParams.has('status') || urlParams.get('view') === 'orders') {
                                activeTabId = 'orders-tab';
                            }

                            // Activate the tab
                            if (activeTabId) {
                                const tabTrigger = document.getElementById(activeTabId);
                                if (tabTrigger) {
                                    const tab = new bootstrap.Tab(tabTrigger);
                                    tab.show();
                                }
                            }

                            // 2. Save tab state on click & Clear URL params
                            const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
                            tabLinks.forEach(tab => {
                                tab.addEventListener('shown.bs.tab', function (event) {
                                    localStorage.setItem('activeAdminTab', event.target.id);

                                    // Clear URL params so refresh honors the new tab (localStorage)
                                    if (window.location.search.length > 0) {
                                        const newUrl = window.location.pathname;
                                        window.history.replaceState(null, null, newUrl);
                                    }
                                });
                            });

                            // 3. Clean URL params
                            if (urlParams.has('product_added') || urlParams.has('product_deleted')) {
                                window.history.replaceState(null, null, window.location.pathname);
                            }
                        });
                    </script>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                            <span>Product List</span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <?= $category_filter ? ucfirst($category_filter) : 'Filter Category' ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="index.php?category=all">All</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=smartphone">Mobile</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=watch">Watch</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=audio">Audio</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=laptop">Laptop</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=camera">Camera</a></li>
                                    <li><a class="dropdown-item" href="index.php?category=home-appliance">Home
                                            Appliance</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th style="width: 60px;">Image</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1;
                                    foreach ($products as $p): ?>
                                        <tr>
                                            <td><?= $sn++ ?></td>
                                            <td>
                                                <?php
                                                $imgSrc = $p['image'];
                                                if (!filter_var($imgSrc, FILTER_VALIDATE_URL)) {
                                                    $imgSrc = "../" . $imgSrc;
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Img"
                                                    class="rounded border bg-white"
                                                    style="width: 50px; height: 50px; object-fit: contain; padding: 2px;">
                                            </td>
                                            <td><?= htmlspecialchars($p['name']) ?></td>
                                            <td>₹<?= number_format($p['price'], 2) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-2"
                                                    data-product='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>'
                                                    onclick="editProduct(this)">Edit</button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDeleteProduct(<?= $p['id'] ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CUSTOMERS TAB -->
                <div class="tab-pane fade" id="customers" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Customer List</span>
                            <!-- Add New Customer removed as per request -->
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>

                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1;
                                    foreach ($customers as $c): ?>
                                        <tr>
                                            <td><?= $sn++ ?></td>
                                            <td><?= htmlspecialchars($c['name']) ?></td>
                                            <td><?= htmlspecialchars($c['email']) ?></td>
                                            <td><?= htmlspecialchars($c['phone']) ?></td>

                                            <td>
                                                <div style="font-size: 0.85rem; max-width: 300px;">
                                                    <?php
                                                    $full_addr_parts = [];
                                                    if (!empty($c['address']))
                                                        $full_addr_parts[] = $c['address'];
                                                    if (!empty($c['locality']))
                                                        $full_addr_parts[] = $c['locality'];
                                                    if (!empty($c['city']))
                                                        $full_addr_parts[] = $c['city'];
                                                    if (!empty($c['state']))
                                                        $full_addr_parts[] = $c['state'];
                                                    if (!empty($c['pincode']))
                                                        $full_addr_parts[] = '- ' . $c['pincode'];

                                                    echo implode(', ', $full_addr_parts);
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info text-white"
                                                    onclick='viewCustomer(<?= json_encode($c) ?>)'>View</button>
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="confirmDeleteCustomer(<?= $c['id'] ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- FEEDBACK TAB -->
                <div class="tab-pane fade" id="feedback" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">Customer Messages</div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $m): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($m['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($m['subject']) ?></td>
                                            <td><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                                            <td><?= date('M d, H:i', strtotime($m['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ADMINS TAB -->
                <div class="tab-pane fade" id="admins" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Admin Users</span>
                            <a href="create_admin.php" class="btn btn-sm btn-success">Add New Admin</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT * FROM admins ORDER BY id ASC");
                                    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($admins as $a):
                                        ?>
                                        <tr>
                                            <td><?= $a['id'] ?></td>
                                            <td><?= htmlspecialchars($a['username']) ?></td>
                                            <td><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                                            <td>
                                                <a href="edit_admin.php?id=<?= $a['id'] ?>"
                                                    class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="confirmDeleteAdmin(<?= $a['id'] ?>)">Delete</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Delete</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Order Details Modal -->
        <div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="orderModalId">Order Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Customer Details Column -->
                            <div class="col-md-7">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">Customer Information</h6>
                                <p class="mb-1"><span class="fw-bold">Name:</span> <span id="modalCustName"></span></p>
                                <p class="mb-1"><span class="fw-bold">Mobile:</span> <span id="modalCustPhone"></span>
                                </p>
                                <p class="mb-3"><span class="fw-bold">Shipping Address:</span> <br><span
                                        id="modalCustAddress" class="text-muted small"></span></p>
                            </div>

                            <!-- Hero Image Column (The circled blank space) -->
                            <div
                                class="col-md-5 d-flex flex-column align-items-center justify-content-center text-center">
                                <img id="modalHeroImage" src="" alt="Product" class="img-fluid rounded shadow-sm mb-3"
                                    style="max-height: 150px; display: none;">

                                <!-- Selected Product Info -->
                                <div id="modalProductDetails" style="display: none;"
                                    class="w-100 p-2 bg-light rounded text-start">
                                    <h6 class="fw-bold mb-1" id="modalProdTitle"></h6>
                                    <span class="badge bg-secondary mb-2" id="modalProdCat"></span>
                                    <h5 class="text-primary fw-bold mb-2">₹<span id="modalProdPrice"></span></h5>
                                    <p class="small text-muted mb-0" id="modalProdDesc"
                                        style="max-height: 100px; overflow-y: auto;"></p>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3 mt-3">Order Items</h6>
                        <div id="modalOrderItems" class="mb-3">
                            <!-- Items populated by JS -->
                        </div>

                        <div class="d-flex justify-content-between border-top pt-3">
                            <span class="h5 mb-0">Total</span>
                            <span class="h5 fw-bold mb-0 text-primary" id="modalTotal"></span>
                        </div>

                        <!-- Refund & Net Pay Display -->
                        <div id="modalRefundSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-danger">Refund Amount:</span>
                                <span class="h6 fw-bold mb-0 text-danger" id="modalRefundTotal"></span>
                            </div>
                            <div
                                class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary border-opacity-25">
                                <span class="fw-bold text-success">Net Total:</span>
                                <span class="h5 fw-bold mb-0 text-success" id="modalNetTotal"></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Details Modal -->
        <div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title fw-bold">Customer Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <img src="../assets/images/admin_avatar.png" alt="Avatar" class="rounded-circle shadow-sm"
                                style="width: 80px; height: 80px;">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold text-uppercase">Name</label>
                                <p class="fw-semibold mb-0" id="viewCustName"></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold text-uppercase">Email</label>
                                <p class="fw-semibold mb-0 text-break" id="viewCustEmail"></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold text-uppercase">Mobile</label>
                                <p class="fw-semibold mb-0" id="viewCustPhone"></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold text-uppercase">Pincode</label>
                                <p class="fw-semibold mb-0" id="viewCustPincode"></p>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted fw-bold text-uppercase">Address</label>
                                <p class="fw-semibold mb-0 text-break" id="viewCustAddress"></p>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold text-uppercase">Locality</label>
                                <p class="fw-semibold mb-0" id="viewCustLocality"></p>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold text-uppercase">City</label>
                                <p class="fw-semibold mb-0" id="viewCustCity"></p>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold text-uppercase">State</label>
                                <p class="fw-semibold mb-0" id="viewCustState"></p>
                            </div>
                            <div class="col-12 mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted fw-bold text-uppercase">Registered On</span>
                                    <span class="fw-semibold" id="viewCustDate"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-primary" onclick="openEditFromView()">Edit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">Edit Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="update_customer" value="1">
                            <input type="hidden" name="cust_id" id="editCustId">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="cust_name" id="editCustName" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="cust_email" id="editCustEmail" class="form-control"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="cust_phone" id="editCustPhone" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="cust_pincode" id="editCustPincode" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address (House No, Street)</label>
                                    <textarea name="cust_address" id="editCustAddress" class="form-control"
                                        rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Locality</label>
                                    <input type="text" name="cust_locality" id="editCustLocality" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="cust_city" id="editCustCity" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" name="cust_state" id="editCustState" class="form-control">
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Customer Confirmation Modal -->
        <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Delete Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="fa-solid fa-user-slash text-danger display-4 mb-3"></i>
                        <h5 class="mb-3">Are you sure?</h5>
                        <p class="text-muted mb-0">Do you really want to delete this customer? All their orders and data
                            will be removed.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteCustomerBtn" class="btn btn-danger fw-bold">Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Product Confirmation Modal -->
        <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Delete Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="fa-solid fa-triangle-exclamation text-danger display-4 mb-3"></i>
                        <h5 class="mb-3">Are you sure?</h5>
                        <p class="text-muted mb-0">Do you really want to delete this product? This process cannot be
                            undone.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteBtn" class="btn btn-danger fw-bold">Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Order Confirmation Modal -->
        <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Cancel Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="fa-solid fa-ban text-danger display-4 mb-3"></i>
                        <h5 class="mb-3">Are you sure?</h5>
                        <p class="text-muted mb-0">Do you really want to cancel this order? This action cannot be
                            undone.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="#" id="confirmCancelOrderBtn" class="btn btn-danger fw-bold">Cancel Order</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Admin Confirmation Modal -->
        <div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Delete Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="fa-solid fa-user-xmark text-danger display-4 mb-3"></i>
                        <h5 class="mb-3">Are you sure?</h5>
                        <p class="text-muted mb-0">Do you really want to delete this admin? This process cannot be
                            undone.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteAdminBtn" class="btn btn-danger fw-bold">Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Order Confirmation Modal -->
        <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">Delete Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="fa-solid fa-trash-can text-danger display-4 mb-3"></i>
                        <h5 class="mb-3">Are you sure?</h5>
                        <p class="text-muted mb-0">Do you really want to delete this order? This process cannot be
                            undone.
                        </p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteOrderForm">
                            <input type="hidden" name="delete_order" value="1">
                            <input type="hidden" name="order_id" id="deleteOrderIdInput">
                            <button type="submit" class="btn btn-danger fw-bold">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            var orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
            var customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
            var editCustomerModal = new bootstrap.Modal(document.getElementById('editCustomerModal'));

            var currentViewedCustomer = null; // Store current customer data

            function viewOrder(order) {
                document.getElementById('orderModalId').innerText = 'Order #' + order.order_id;

                document.getElementById('modalCustName').innerText = order.customer_name;
                document.getElementById('modalCustPhone').innerText = order.shipping_phone || order.shipping_phone;
                document.getElementById('modalCustAddress').innerText = order.shipping_address;

                // Parse custom delimiter string: Name::Qty::Image::Price::Desc::Category || ...
                var itemsHtml = '<ul class="list-group list-group-flush">';
                var firstItemData = null;

                if (order.product_details) {
                    var items = order.product_details.split('||');
                    items.forEach(function (itemStr, index) {
                        var parts = itemStr.split('::');
                        if (parts.length >= 3) {
                            var name = parts[0];
                            var qty = parts[1];
                            var img = parts[2];
                            var price = parts[3] || '0';
                            var desc = parts[4] || ''; // Description
                            var cat = parts[5] || 'General'; // Category
                            var itemStatus = parts[6] || 'pending'; // Status
                            var itemId = (parts[7] || '').trim(); // Item ID - Trim whitespace!

                            // FILTER in Modal too if URL has status=canelled or refund?
                            // Users typically want to see EVERYTHING in modal, but for Refund focus maybe filter?
                            // Let's check the URL parameter
                            const urlParams = new URLSearchParams(window.location.search);
                            const viewStatus = urlParams.get('status');

                            // Updated Filter Logic:
                            // Only filter items if the order itself is not fully cancelled.
                            if (order.status !== 'cancelled') {
                                if (viewStatus === 'refund' && itemStatus !== 'cancelled') {
                                    return; // Skip non-cancelled items in refund view
                                }
                                if (viewStatus === 'paid' && itemStatus === 'cancelled') {
                                    return; // Skip cancelled items in paid view
                                }
                                if (viewStatus === 'cancelled' && itemStatus !== 'cancelled') {
                                    return; // Skip non-cancelled items in cancelled view
                                }
                            }

                            // Handle relative paths
                            if (img && !img.startsWith('http')) {
                                img = '../' + img;
                            }

                            // Store data for click handler
                            var itemData = { name, img, price, desc, cat };
                            var itemDataJson = JSON.stringify(itemData).replace(/"/g, '&quot;');

                            // Capture first item to show by default
                            if (index === 0) {
                                firstItemData = itemData;
                            }

                            var badgeClass = 'bg-secondary';
                            if (itemStatus === 'delivered' || itemStatus === 'paid') badgeClass = 'bg-success';
                            else if (itemStatus === 'shipped') badgeClass = 'bg-info';

                            itemsHtml += `
                        <li class="list-group-item d-flex align-items-center gap-3 px-0 p-2 border rounded mb-2" 
                            style="transition: background 0.2s; cursor: pointer;"
                            onclick='updateProductDetails(${itemDataJson})'>
                            <img src="${img}" alt="${name}" class="rounded border bg-white" style="width: 50px; height: 50px; object-fit: contain; padding: 2px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-dark fw-semibold">${name} 
                                    ${itemStatus === 'cancelled'
                                    ? (order.payment_status === 'paid'
                                        ? '<span class="badge bg-danger">Refund Processing</span>'
                                        : '<span class="badge bg-danger">Cancelled</span>')
                                    : (itemStatus === 'pending' ? '' : '<span class="badge ' + badgeClass + ' small">' + itemStatus + '</span>')}
                                </h6>
                                <small class="text-muted">Quantity: ${qty}</small>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="fw-semibold">₹${parseFloat(price).toLocaleString('en-IN')}</span>
                            </div>
                        </li>`;
                        }
                    });
                }
                itemsHtml += '</ul>';

                document.getElementById('modalOrderItems').innerHTML = itemsHtml;

                // Calculate Refund Total from Cancelled Items
                let totalAmount = parseFloat(order.total_amount);
                let refundTotal = 0;

                if (order.product_details) {
                    var items = order.product_details.split('||');
                    items.forEach(function (itemStr) {
                        var parts = itemStr.split('::');
                        if (parts.length >= 7) {
                            var qty = parseInt(parts[1]) || 1;
                            var price = parseFloat(parts[3]) || 0;
                            var itemStatus = parts[6] || 'pending';

                            if (itemStatus === 'cancelled') {
                                refundTotal += (price * qty);
                            }
                        }
                    });
                }

                document.getElementById('modalTotal').innerText = '₹' + totalAmount.toLocaleString('en-IN');

                // Update Refund & Net Pay Display
                const refundSection = document.getElementById('modalRefundSection');
                const refundEl = document.getElementById('modalRefundTotal');
                const netEl = document.getElementById('modalNetTotal');

                if (refundTotal > 0) {
                    refundSection.style.display = 'block';
                    refundEl.innerText = '- ₹' + refundTotal.toLocaleString('en-IN');
                    netEl.innerText = '₹' + (totalAmount - refundTotal).toLocaleString('en-IN');
                } else {
                    refundSection.style.display = 'none';
                }

                // Initialize Header/Details with first item
                if (firstItemData) {
                    updateProductDetails(firstItemData);
                } else {
                    document.getElementById('modalHeroImage').style.display = 'none';
                    document.getElementById('modalProductDetails').style.display = 'none';
                }

                orderModal.show();
            }

            function updateProductDetails(data) {
                // Update Hero Image
                var heroImgEl = document.getElementById('modalHeroImage');
                heroImgEl.src = data.img;
                heroImgEl.style.display = 'block';

                // Update Details
                document.getElementById('modalProdTitle').innerText = data.name;
                document.getElementById('modalProdCat').innerText = data.cat.toUpperCase();
                document.getElementById('modalProdPrice').innerText = parseFloat(data.price).toLocaleString('en-IN');
                document.getElementById('modalProdDesc').innerText = data.desc;

                document.getElementById('modalProductDetails').style.display = 'block';
            }

            function viewCustomer(cust) {
                currentViewedCustomer = cust; // Save for Edit

                document.getElementById('viewCustName').innerText = cust.name;
                document.getElementById('viewCustEmail').innerText = cust.email;
                document.getElementById('viewCustPhone').innerText = cust.phone;

                // Populate separate fields matching grid
                document.getElementById('viewCustAddress').innerText = cust.address || 'N/A';
                document.getElementById('viewCustLocality').innerText = cust.locality || 'N/A';
                document.getElementById('viewCustCity').innerText = cust.city || 'N/A';
                document.getElementById('viewCustState').innerText = cust.state || 'N/A';
                document.getElementById('viewCustPincode').innerText = cust.pincode || 'N/A';

                document.getElementById('viewCustDate').innerText = cust.created_at;

                customerModal.show();
            }

            function openEditFromView() {
                if (currentViewedCustomer) {
                    customerModal.hide();
                    editCustomer(currentViewedCustomer);
                }
            }

            function editCustomer(cust) {
                document.getElementById('editCustId').value = cust.id;
                document.getElementById('editCustName').value = cust.name;
                document.getElementById('editCustEmail').value = cust.email;
                document.getElementById('editCustPhone').value = cust.phone;
                document.getElementById('editCustAddress').value = cust.address;

                // Populate extended fields
                document.getElementById('editCustLocality').value = cust.locality || '';
                document.getElementById('editCustCity').value = cust.city || '';
                document.getElementById('editCustState').value = cust.state || '';
                document.getElementById('editCustPincode').value = cust.pincode || '';

                editCustomerModal.show();
            }

            // Delete Product Modal Trigger
            function confirmDeleteProduct(id) {
                const deleteUrl = 'delete_product.php?id=' + id;
                document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));

                deleteModal.show();
            }

            // Delete Admin Modal Trigger
            function confirmDeleteAdmin(id) {
                const deleteUrl = 'delete_admin.php?id=' + id;
                document.getElementById('confirmDeleteAdminBtn').setAttribute('href', deleteUrl);
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
                deleteModal.show();
            }

            // Delete Customer Modal Trigger
            function confirmDeleteCustomer(id) {
                const deleteUrl = 'delete.php?id=' + id;
                document.getElementById('confirmDeleteCustomerBtn').setAttribute('href', deleteUrl);
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteCustomerModal'));
                deleteModal.show();
            }

            // Cancel Order Modal Trigger
            // Cancel Order Modal Trigger
            function confirmCancelOrder(id) {
                const cancelUrl = 'cancel_order.php?id=' + id;
                document.getElementById('confirmCancelOrderBtn').setAttribute('href', cancelUrl);
                var cancelModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
                cancelModal.show();
            }

            // Delete Order Modal Trigger
            function confirmDeleteOrder(id) {
                document.getElementById('deleteOrderIdInput').value = id;
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
                deleteModal.show();
            }

            function showCustomersTab() {
                var triggerEl = document.querySelector('#customers-tab');
                if (triggerEl) {
                    var tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                    // Scroll to table
                    document.getElementById('customers').scrollIntoView({ behavior: 'smooth' });
                }
            }

            // Edit Product Modal Logic
            var editProductModal = null;

            function editProduct(btn) {
                if (!editProductModal) {
                    editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                }

                const product = JSON.parse(btn.getAttribute('data-product'));

                document.getElementById('editProdId').value = product.id;
                document.getElementById('editProdName').value = product.name;
                document.getElementById('editProdPrice').value = product.price;
                document.getElementById('editProdDesc').value = product.description;
                document.getElementById('editProdCategory').value = product.category;
                // Existing image hidden/preview
                document.getElementById('editProdExistingImage').value = product.image;

                // Show current image preview
                var imgPreview = document.getElementById('editProdImgPreview');
                var imgSrc = product.image;
                if (imgSrc && !imgSrc.startsWith('http')) {
                    imgSrc = '../' + imgSrc;
                }
                imgPreview.src = imgSrc;
                imgPreview.style.display = 'block';

                editProductModal.show();
            }
        </script>

        <!-- Edit Product Modal -->
        <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title fw-bold">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_product" value="1">
                            <input type="hidden" name="product_id" id="editProdId">
                            <input type="hidden" name="existing_image" id="editProdExistingImage">

                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" id="editProdName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (₹)</label>
                                <input type="number" step="0.01" name="price" id="editProdPrice" class="form-control"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" id="editProdCategory" class="form-select" required>
                                    <option value="" selected disabled>Select Category</option>
                                    <option value="smartphone">Mobile</option>
                                    <option value="watch">Watch</option>
                                    <option value="audio">Audio</option>
                                    <option value="laptop">Laptop</option>
                                    <option value="camera">Photography</option>
                                    <option value="home-appliance">Home Appliance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="editProdDesc" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Product Image (Optional)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <div class="mt-2 text-center">
                                    <small class="text-muted d-block mb-1">Current Image:</small>
                                    <img id="editProdImgPreview" src="" alt="Preview"
                                        style="max-height: 100px; display: none;" class="rounded border mx-auto">
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning fw-bold text-white">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Toast Container -->
</body>

</html>