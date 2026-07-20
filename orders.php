<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// ── Auto-migrate: silently add new columns if they don't exist ────────────
$_migrations = [
    "ALTER TABLE orders ADD COLUMN payment_method  VARCHAR(50)    DEFAULT 'cod'",
    "ALTER TABLE orders ADD COLUMN payment_status  ENUM('paid','unpaid') DEFAULT 'unpaid'",
    "ALTER TABLE orders ADD COLUMN shipping_name   VARCHAR(100)   NULL",
    "ALTER TABLE orders ADD COLUMN shipping_phone  VARCHAR(20)    NULL",
    "ALTER TABLE orders ADD COLUMN shipping_address TEXT          NULL",
    "ALTER TABLE orders ADD COLUMN shipped_at      DATETIME       NULL",
    "ALTER TABLE orders ADD COLUMN delivered_at    DATETIME       NULL",
    "ALTER TABLE orders ADD COLUMN tracking_note   VARCHAR(255)   NULL",
    "ALTER TABLE orders  MODIFY COLUMN status ENUM('pending','paid','unpaid','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending'",
    "ALTER TABLE order_items MODIFY COLUMN status ENUM('pending','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending'",
];
foreach ($_migrations as $_sql) {
    try { $pdo->exec($_sql); } catch (PDOException $_e) { /* column already exists — ignore */ }
}

// Fetch User
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch Orders with all needed columns
$sql = "
    SELECT o.id as order_id, o.total_amount, o.status, o.created_at,
           o.payment_status, o.payment_method,
           o.shipped_at, o.delivered_at, o.tracking_note,
           oi.id as item_id, oi.product_id, oi.quantity, oi.price, oi.status as item_status,
           p.name as product_name, p.image as product_image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$raw_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by Order ID
$orders = [];
foreach ($raw_orders as $row) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id'             => $oid,
            'total'          => $row['total_amount'],
            'status'         => $row['status'],
            'payment_status' => $row['payment_status'] ?? 'unpaid',
            'payment_method' => $row['payment_method'] ?? 'cod',
            'date'           => $row['created_at'],
            'shipped_at'     => $row['shipped_at'],
            'delivered_at'   => $row['delivered_at'],
            'tracking_note'  => $row['tracking_note'],
            'items'          => []
        ];
    }
    $orders[$oid]['items'][] = [
        'item_id'    => $row['item_id'],
        'product_id' => $row['product_id'],
        'name'       => $row['product_name'],
        'image'      => $row['product_image'],
        'quantity'   => $row['quantity'],
        'price'      => $row['price'],
        'status'     => $row['item_status'] ?? 'pending'
    ];
}
?>

<!-- ═══════════════════════════════ STYLES ════════════════════════════════ -->
<style>
/* ── Tracking Timeline ─────────────────────────────────────────────────── */
.tracking-timeline {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
    padding: 0 8px;
    margin: 0 0 1.5rem 0;
}
.tracking-timeline::before {
    content: '';
    position: absolute;
    top: 22px;
    left: 8%;
    right: 8%;
    height: 3px;
    background: #e9ecef;
    z-index: 0;
}
.tracking-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 1;
}
.tracking-step .step-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e9ecef;
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
    transition: all 0.3s ease;
    margin-bottom: 6px;
}
.tracking-step.done .step-icon {
    background: #198754;
    color: #fff;
    box-shadow: 0 0 0 2px #198754;
}
.tracking-step.active .step-icon {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.25);
    animation: pulse-ring 1.5s ease-out infinite;
}
.tracking-step.cancelled-step .step-icon {
    background: #dc3545;
    color: #fff;
    box-shadow: 0 0 0 2px #dc3545;
}
@keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 3px rgba(13,110,253,0.25); }
    70%  { box-shadow: 0 0 0 8px rgba(13,110,253,0); }
    100% { box-shadow: 0 0 0 0px rgba(13,110,253,0); }
}
.tracking-step .step-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-align: center;
    color: #adb5bd;
    line-height: 1.3;
    max-width: 75px;
}
.tracking-step.done .step-label,
.tracking-step.active .step-label {
    color: #333;
}
.tracking-step.cancelled-step .step-label {
    color: #dc3545;
}
/* Progress bar fill */
.tracking-progress-fill {
    position: absolute;
    top: 22px;
    left: 8%;
    height: 3px;
    background: linear-gradient(90deg, #198754, #0d6efd);
    z-index: 0;
    transition: width 0.5s ease;
    border-radius: 2px;
}

/* ── Payment badge ─────────────────────────────────────────────────────── */
.pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}
.pay-badge-cod      { background: #fff3cd; color: #856404; }
.pay-badge-online   { background: #d1e7dd; color: #0a3622; }
.pay-badge-unpaid   { background: #f8d7da; color: #58151c; }
.pay-badge-paid     { background: #d1e7dd; color: #0a3622; }

/* ── Card ──────────────────────────────────────────────────────────────── */
.order-card { border-radius: 16px; overflow: hidden; transition: box-shadow 0.2s; }
.order-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important; }

.spacing-1 { letter-spacing: 1px; }
.hover-link:hover { text-decoration: underline !important; color: #0d6efd !important; }
</style>

<!-- ════════════════════════════════ HTML ════════════════════════════════ -->
<div class="bg-light min-vh-100 py-4">
    <div class="container">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm mb-3 border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <img src="assets/images/admin_avatar.png" class="rounded-circle border" width="50" height="50" alt="Avatar">
                        <div>
                            <small class="text-muted">Hello,</small>
                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($user['name']) ?></h6>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="list-group list-group-flush rounded-3">
                        <div class="p-3 pb-0">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="bi bi-person-fill text-muted fs-5"></i>
                                <span class="fw-bold text-muted">ACCOUNT SETTINGS</span>
                            </div>
                        </div>
                        <a href="profile.php" class="list-group-item list-group-item-action border-0 ps-5 py-2 text-muted fw-bold">Profile Information</a>
                        <a href="orders.php" class="list-group-item list-group-item-action border-0 ps-5 py-2 text-primary bg-light fw-bold">My Orders</a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action border-0 ps-5 py-2 text-muted fw-bold">My Wishlist</a>
                        <div class="border-top text-center p-3">
                            <a href="logout.php" class="text-decoration-none fw-bold text-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-lg-9">
                <h4 class="mb-4 fw-bold">My Orders</h4>

                <?php if (empty($orders)): ?>
                    <div class="card shadow-sm border-0 text-center p-5">
                        <div class="mb-3"><i class="bi bi-bag-x fs-1 text-muted opacity-50 display-1"></i></div>
                        <h4 class="fw-bold">No orders found</h4>
                        <p class="text-muted">Looks like you haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-primary px-4 py-2 rounded-pill fw-bold mt-2">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <?php
                    $seq_no = count($orders);
                    foreach ($orders as $order):
                        $status = strtolower(trim($order['status']));

                        // ── Check if ALL items are cancelled ──────────────────
                        $all_items_cancelled = true;
                        foreach ($order['items'] as $item) {
                            if (strtolower($item['status']) !== 'cancelled') {
                                $all_items_cancelled = false;
                                break;
                            }
                        }
                        if ($all_items_cancelled) $status = 'cancelled';

                        $is_cancelled        = ($status === 'cancelled');
                        $is_delivered        = ($status === 'delivered');
                        $is_out_for_delivery = ($status === 'out_for_delivery');
                        $is_shipped          = ($status === 'shipped');
                        $is_paid             = ($status === 'paid' || $order['payment_status'] === 'paid');
                        $is_cod              = ($order['payment_method'] === 'cod');

                        // ── Header badge ──────────────────────────────────────
                        $status_color = 'warning';
                        $status_label = 'Order Placed';
                        $icon         = 'bi-clock-fill';

                        if ($is_cancelled) {
                            $status_color = 'danger'; $icon = 'bi-x-circle-fill';
                            $status_label = ($order['payment_status'] === 'paid') ? 'Refund Processing' : 'Cancelled';
                        } elseif ($is_delivered) {
                            $status_color = 'success'; $icon = 'bi-check-circle-fill'; $status_label = 'Delivered';
                        } elseif ($is_out_for_delivery) {
                            $status_color = 'primary'; $icon = 'bi-bicycle'; $status_label = 'Out for Delivery';
                        } elseif ($is_shipped) {
                            $status_color = 'info'; $icon = 'bi-truck'; $status_label = 'Shipped';
                        } elseif ($is_paid) {
                            $status_color = 'success'; $icon = 'bi-bag-check-fill'; $status_label = 'Confirmed';
                        }

                        // ── Timeline step index (0=placed,1=confirmed,2=shipped,3=ofd,4=delivered) ──
                        $timeline_step = 0; // "Order Placed"
                        if ($is_paid && !$is_shipped && !$is_out_for_delivery && !$is_delivered) $timeline_step = 1;
                        elseif ($is_shipped)          $timeline_step = 2;
                        elseif ($is_out_for_delivery) $timeline_step = 3;
                        elseif ($is_delivered)        $timeline_step = 4;

                        // Fill width = (step / 4) * 84% (covers between left 8% and right 8%)
                        $fill_pct = ($timeline_step / 4) * 84;
                    ?>

                    <div class="card border-0 shadow-sm mb-4 order-card">
                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-<?= $status_color ?> bg-opacity-10 text-<?= $status_color ?> p-2 rounded-circle">
                                        <i class="bi <?= $icon ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase fw-bold text-<?= $status_color ?> small spacing-1">
                                            <?= $status_label ?>
                                        </div>
                                        <small class="text-muted fw-bold">Order #<?= $seq_no-- ?></small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <!-- Payment Method Badge -->
                                    <?php if ($is_cod): ?>
                                        <span class="pay-badge pay-badge-cod"><i class="bi bi-cash-coin"></i> Cash on Delivery</span>
                                    <?php else: ?>
                                        <span class="pay-badge pay-badge-online"><i class="bi bi-shield-check"></i> Online Payment</span>
                                    <?php endif; ?>
                                    <!-- Payment Status Badge -->
                                    <?php if ($order['payment_status'] === 'paid'): ?>
                                        <span class="pay-badge pay-badge-paid"><i class="bi bi-check2-circle"></i> Paid</span>
                                    <?php elseif (!$is_cancelled): ?>
                                        <span class="pay-badge pay-badge-unpaid"><i class="bi bi-hourglass-split"></i> Unpaid</span>
                                    <?php endif; ?>
                                    <div class="text-end ms-2">
                                        <div class="text-muted small fw-bold">Placed On</div>
                                        <div class="fw-bold text-dark"><?= date('D, M d, Y', strtotime($order['date'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4">

                            <!-- ── Tracking Timeline ────────────────────────── -->
                            <?php if (!$is_cancelled): ?>
                            <div class="position-relative mb-4">
                                <div class="tracking-timeline">
                                    <!-- Progress fill bar -->
                                    <div class="tracking-progress-fill" style="width:<?= $fill_pct ?>%;"></div>

                                    <?php
                                    $steps = [
                                        ['icon' => 'bi-bag-plus-fill',     'label' => 'Order Placed'],
                                        ['icon' => 'bi-bag-check-fill',    'label' => 'Confirmed'],
                                        ['icon' => 'bi-truck',             'label' => 'Shipped'],
                                        ['icon' => 'bi-bicycle',           'label' => 'Out for Delivery'],
                                        ['icon' => 'bi-house-check-fill',  'label' => 'Delivered'],
                                    ];
                                    foreach ($steps as $i => $step):
                                        if ($i < $timeline_step)        $cls = 'done';
                                        elseif ($i === $timeline_step)  $cls = 'active';
                                        else                            $cls = '';
                                    ?>
                                    <div class="tracking-step <?= $cls ?>">
                                        <div class="step-icon">
                                            <i class="bi <?= $step['icon'] ?>"></i>
                                        </div>
                                        <div class="step-label"><?= $step['label'] ?></div>
                                        <?php if ($i === 2 && $order['shipped_at']): ?>
                                            <div style="font-size:0.63rem;color:#6c757d;text-align:center;"><?= date('M d', strtotime($order['shipped_at'])) ?></div>
                                        <?php endif; ?>
                                        <?php if ($i === 4 && $order['delivered_at']): ?>
                                            <div style="font-size:0.63rem;color:#198754;text-align:center;"><?= date('M d', strtotime($order['delivered_at'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Cancelled Message -->
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-x-circle-fill me-2 fs-4"></i>
                                <div>
                                    <strong>This order has been cancelled<?= $order['payment_status'] === 'paid' ? '. Refund has been initiated.' : '.' ?></strong>
                                    <div class="small">If you have any questions, please <a href="contact.php" class="text-danger fw-bold">contact support</a>.</div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <hr class="my-3 opacity-25">

                            <!-- ── Order Items ──────────────────────────────── -->
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($order['items'] as $item):
                                    $item_status      = strtolower(trim($item['status']));
                                    $is_item_cancelled    = ($item_status === 'cancelled');
                                    $is_item_delivered    = ($item_status === 'delivered' || $status === 'delivered');
                                    $is_item_shipped      = ($item_status === 'shipped'   || $status === 'shipped');
                                    $is_item_ofd          = ($item_status === 'out_for_delivery' || $status === 'out_for_delivery');

                                    // Can cancel only if item/order not yet dispatched
                                    $non_cancellable_statuses = ['shipped','out_for_delivery','delivered','cancelled'];
                                    $can_cancel = !$is_item_cancelled && !in_array($status, $non_cancellable_statuses) && !in_array($item_status, $non_cancellable_statuses);

                                    // Item status badge
                                    if ($is_item_cancelled) {
                                        $item_badge = $order['payment_status'] === 'paid'
                                            ? '<span class="badge bg-danger">Refund Processing</span>'
                                            : '<span class="badge bg-danger">Cancelled</span>';
                                    } elseif ($is_item_delivered) {
                                        $item_badge = '<span class="badge bg-success">Delivered</span>';
                                    } elseif ($is_item_ofd) {
                                        $item_badge = '<span class="badge bg-primary">Out for Delivery</span>';
                                    } elseif ($is_item_shipped) {
                                        $item_badge = '<span class="badge bg-info">Shipped</span>';
                                    } elseif ($item_status === 'paid') {
                                        $item_badge = '<span class="badge bg-success">Confirmed</span>';
                                    } else {
                                        $item_badge = '';
                                    }
                                ?>
                                <div class="d-flex align-items-center gap-3 <?= $is_item_cancelled ? 'opacity-50' : '' ?>">
                                    <a href="javascript:void(0)" onclick="openQuickView(<?= $item['product_id'] ?>)" class="flex-shrink-0">
                                        <div class="bg-light rounded p-2 border">
                                            <img src="<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 class="img-fluid" style="width:60px;height:60px;object-fit:contain;">
                                        </div>
                                    </a>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-bold text-dark">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                    <?= $item_badge ?>
                                                </h6>
                                                <p class="mb-1 text-muted small">Quantity: <?= $item['quantity'] ?></p>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold fs-5">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                                <div class="text-muted small">₹<?= number_format($item['price'], 2) ?> each</div>
                                            </div>
                                        </div>
                                        <!-- Action Buttons -->
                                        <div class="mt-2 d-flex gap-2">
                                            <?php if ($can_cancel): ?>
                                                <button onclick="cancelItem(<?= $order['id'] ?>, <?= $item['item_id'] ?>)"
                                                    class="btn btn-sm btn-outline-danger py-0 px-2 small fw-bold"
                                                    style="font-size:0.75rem;">
                                                    Cancel Item
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($is_item_delivered): ?>
                                                <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>
                                            <?php elseif ($is_item_ofd): ?>
                                                <span class="text-primary small fw-bold"><i class="bi bi-bicycle me-1"></i>On the way</span>
                                            <?php elseif ($is_item_shipped): ?>
                                                <span class="text-info small fw-bold"><i class="bi bi-truck me-1"></i>Shipped</span>
                                            <?php elseif ($is_item_cancelled): ?>
                                                <span class="text-danger small fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Cancelled</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($order['tracking_note'])): ?>
                            <div class="alert alert-light border mt-3 py-2 small">
                                <i class="bi bi-info-circle me-1"></i> <strong>Update:</strong> <?= htmlspecialchars($order['tracking_note']) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer -->
                        <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> Need help?
                                <a href="contact.php" class="text-primary text-decoration-none">Contact Us</a>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-muted text-uppercase small fw-bold">Total Amount</div>
                                <div class="fw-bold fs-4 text-dark">₹<?= number_format($order['total'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div><!-- /col -->
        </div>
    </div>
</div>

<!-- ══════════════════════ Cancel Confirmation Modal ═════════════════════ -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-danger" id="cancelModalTitle">Cancel Item?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-warning display-4 mb-3 d-block"></i>
                <p class="mb-0 text-muted" id="cancelModalBody">Are you sure you want to cancel this item? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">No, Keep It</button>
                <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmCancelBtn">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
let orderIdToCancel = null;
let itemIdToCancel  = null;
let cancelModal     = null;

function cancelItem(orderId, itemId) {
    if (!cancelModal) {
        cancelModal = new bootstrap.Modal(document.getElementById('cancelConfirmModal'));
    }
    orderIdToCancel = orderId;
    itemIdToCancel  = itemId;
    cancelModal.show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', function () {
    if (!orderIdToCancel || !itemIdToCancel) return;

    const btn = this;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelling...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action',   'cancel_item');
    formData.append('order_id', orderIdToCancel);
    formData.append('item_id',  itemIdToCancel);

    fetch('includes/order_action.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
                btn.innerHTML = 'Yes, Cancel';
                btn.disabled = false;
                if (cancelModal) cancelModal.hide();
            }
        })
        .catch(() => {
            alert('An error occurred. Please try again.');
            btn.innerHTML = 'Yes, Cancel';
            btn.disabled = false;
            if (cancelModal) cancelModal.hide();
        });
});
</script>