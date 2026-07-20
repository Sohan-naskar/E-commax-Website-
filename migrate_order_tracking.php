<?php
// migrate_order_tracking.php
// Run this file ONCE in your browser: http://localhost/PHP_College_5.2/eCommax_Website/migrate_order_tracking.php
// It is safe to run multiple times (uses IF NOT EXISTS / MODIFY guards).

require_once 'config/database.php';

$results = [];
$errors  = [];

function run($pdo, $sql, $label, &$results, &$errors) {
    try {
        $pdo->exec($sql);
        $results[] = "✅ " . $label;
    } catch (PDOException $e) {
        // Ignore "Duplicate column" errors — column already exists
        if (strpos($e->getMessage(), 'Duplicate column') !== false ||
            strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = "⚠️ " . $label . " (already exists, skipped)";
        } else {
            $errors[] = "❌ " . $label . " → " . $e->getMessage();
        }
    }
}

// ─────────────────────────────────────────────
// 1. ORDERS TABLE — update status ENUM
// ─────────────────────────────────────────────
run($pdo,
    "ALTER TABLE orders 
     MODIFY COLUMN status ENUM('pending','paid','unpaid','shipped','out_for_delivery','delivered','cancelled') 
     DEFAULT 'pending'",
    "orders.status ENUM expanded (added pending, out_for_delivery)",
    $results, $errors
);

// 2. Add payment_method column
run($pdo,
    "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cod'",
    "orders.payment_method column added",
    $results, $errors
);

// 3. Add payment_status column (if not already there)
run($pdo,
    "ALTER TABLE orders ADD COLUMN payment_status ENUM('paid','unpaid') DEFAULT 'unpaid'",
    "orders.payment_status column added",
    $results, $errors
);

// 4. Add shipping_name column
run($pdo,
    "ALTER TABLE orders ADD COLUMN shipping_name VARCHAR(100) NULL",
    "orders.shipping_name column added",
    $results, $errors
);

// 5. Add shipping_phone column
run($pdo,
    "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(20) NULL",
    "orders.shipping_phone column added",
    $results, $errors
);

// 6. Add shipping_address column
run($pdo,
    "ALTER TABLE orders ADD COLUMN shipping_address TEXT NULL",
    "orders.shipping_address column added",
    $results, $errors
);

// 7. Add shipped_at timestamp
run($pdo,
    "ALTER TABLE orders ADD COLUMN shipped_at DATETIME NULL",
    "orders.shipped_at column added",
    $results, $errors
);

// 8. Add delivered_at timestamp
run($pdo,
    "ALTER TABLE orders ADD COLUMN delivered_at DATETIME NULL",
    "orders.delivered_at column added",
    $results, $errors
);

// 9. Add tracking_note
run($pdo,
    "ALTER TABLE orders ADD COLUMN tracking_note VARCHAR(255) NULL",
    "orders.tracking_note column added",
    $results, $errors
);

// ─────────────────────────────────────────────
// 10. ORDER_ITEMS TABLE — update status ENUM
// ─────────────────────────────────────────────
run($pdo,
    "ALTER TABLE order_items 
     MODIFY COLUMN status ENUM('pending','shipped','out_for_delivery','delivered','cancelled') 
     DEFAULT 'pending'",
    "order_items.status ENUM expanded (added out_for_delivery)",
    $results, $errors
);

// ─────────────────────────────────────────────
// 11. Backfill: set payment_method for existing orders
//     COD orders = unpaid status → payment_method = 'cod'
//     Paid orders → payment_method = 'online'
// ─────────────────────────────────────────────
run($pdo,
    "UPDATE orders SET payment_method = 'cod'    WHERE payment_status = 'unpaid' AND (payment_method IS NULL OR payment_method = '')",
    "Backfill: existing unpaid orders → payment_method = cod",
    $results, $errors
);
run($pdo,
    "UPDATE orders SET payment_method = 'online' WHERE payment_status = 'paid'   AND (payment_method IS NULL OR payment_method = '')",
    "Backfill: existing paid orders → payment_method = online",
    $results, $errors
);

// ─────────────────────────────────────────────
// Done — Show Results
// ─────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DB Migration — Order Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container" style="max-width:700px;">
        <h3 class="fw-bold mb-4">🛠️ Order Tracking — DB Migration</h3>

        <?php foreach ($results as $r): ?>
            <div class="alert alert-success py-2 mb-2"><?= $r ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger py-2 mb-2"><?= $e ?></div>
        <?php endforeach; ?>

        <?php if (empty($errors)): ?>
            <div class="alert alert-primary fw-bold mt-3">
                ✅ All migrations completed successfully! You can now delete this file.
            </div>
        <?php else: ?>
            <div class="alert alert-warning fw-bold mt-3">
                ⚠️ Some steps failed. Review errors above.
            </div>
        <?php endif; ?>

        <a href="index.php" class="btn btn-dark mt-3">← Back to Site</a>
    </div>
</body>
</html>
