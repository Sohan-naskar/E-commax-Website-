<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// Fetch User for Sidebar
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch Orders with Product Details
// We group by order ID to show one card per order, but list products inside
$sql = "
    SELECT o.id as order_id, o.total_amount, o.status, o.created_at, o.payment_status,
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

// Group products by Order ID
$orders = [];
foreach ($raw_orders as $row) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id' => $oid,
            'total' => $row['total_amount'],
            'status' => $row['status'],
            'payment_status' => $row['payment_status'] ?? 'pending',
            'date' => $row['created_at'],
            'items' => []
        ];
    }
    $orders[$oid]['items'][] = [
        'item_id' => $row['item_id'], // Need primary key of item to cancel strictly it
        'product_id' => $row['product_id'],
        'name' => $row['product_name'],
        'image' => $row['product_image'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'status' => $row['item_status'] ?? 'pending'
    ];
}
?>

<div class="bg-light min-vh-100 py-4">
    <div class="container">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-lg-3 mb-4">
                <!-- Hello User Card -->
                <div class="card shadow-sm mb-3 border-0">
                    <div class="card-body d-flex align-items-center gap-3">
                        <img src="assets/images/admin_avatar.png" class="rounded-circle border" width="50" height="50"
                            alt="Avatar">
                        <div>
                            <small class="text-muted">Hello,</small>
                            <h6 class="mb-0 fw-bold">
                                <?= htmlspecialchars($user['name']) ?>
                            </h6>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <div class="card shadow-sm border-0">
                    <div class="list-group list-group-flush rounded-3">

                        <div class="p-3 pb-0">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="bi bi-person-fill text-muted fs-5"></i>
                                <span class="fw-bold text-muted">ACCOUNT SETTINGS</span>
                            </div>
                        </div>

                        <a href="profile.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 text-muted fw-bold">Profile
                            Information</a>

                        <a href="orders.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 text-primary bg-light fw-bold">My
                            Orders</a>

                        <a href="wishlist.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 text-muted fw-bold">My
                            Wishlist</a>



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
                        <div class="mb-3">
                            <i class="bi bi-bag-x fs-1 text-muted opacity-50 display-1"></i>
                        </div>
                        <h4 class="fw-bold">No orders found</h4>
                        <p class="text-muted">Looks like you haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-primary px-4 py-2 rounded-pill fw-bold mt-2">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <?php
                    $seq_no = count($orders);
                    foreach ($orders as $order):
                        $status = strtolower($order['status']);

                        // Check if ALL items are cancelled
                        $all_items_cancelled = true;
                        if (!empty($order['items'])) {
                            foreach ($order['items'] as $item) {
                                if ($item['status'] != 'cancelled') {
                                    $all_items_cancelled = false;
                                    break;
                                }
                            }
                        } else {
                            $all_items_cancelled = false;
                        }

                        // Override status if all items are cancelled
                        if ($all_items_cancelled) {
                            $status = 'cancelled';
                        }

                        $is_cancelled = ($status == 'cancelled');
                        $is_delivered = ($status == 'delivered');
                        $is_shipped = ($status == 'shipped');
                        $is_paid = ($status == 'paid' || $order['payment_status'] == 'paid');

                        // Status Progress Logic (0-100)
                        $progress = 0;
                        if ($is_delivered)
                            $progress = 100;
                        elseif ($is_shipped)
                            $progress = 66;
                        else
                            $progress = 33; // Processing/Placed (Paid or Unpaid)
                
                        // Color & Label Logic
                        $status_color = 'warning';
                        $status_label = 'Processing';
                        $icon = 'bi-clock-fill';

                        if ($is_cancelled) {
                            $status_color = 'danger';
                            if ($is_paid) {
                                $status_label = 'Refund Processing';
                            } else {
                                $status_label = 'Cancelled';
                            }
                            $icon = 'bi-x-circle-fill';
                        } elseif ($is_delivered) {
                            $status_color = 'success';
                            $status_label = 'Delivered';
                            $icon = 'bi-check-circle-fill';
                        } elseif ($is_shipped) {
                            $status_color = 'info';
                            $status_label = 'Shipped';
                            $icon = 'bi-truck';
                        } elseif ($is_paid) {
                            $status_color = 'success';
                            $status_label = 'Paid';
                            $icon = 'bi-check-circle-fill';
                        }
                        ?>
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden order-card">
                            <!-- Card Header -->
                            <div class="card-header bg-white border-bottom p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="bg-<?= $status_color ?> bg-opacity-10 text-<?= $status_color ?> p-2 rounded-circle">
                                            <i class="bi <?= $icon ?> fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="text-uppercase fw-bold text-<?= $status_color ?> small mb-0 spacing-1">
                                                <?= $status_label ?>
                                            </div>
                                            <small class="text-muted fw-bold">Order #<?= $seq_no-- ?></small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-muted small fw-bold">Placed On</div>
                                        <div class="fw-bold text-dark"><?= date('D, M d, Y', strtotime($order['date'])) ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-4">
                                <!-- Progress Tracker or Cancelled Message -->
                                <?php if ($is_cancelled): ?>
                                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                        <i class="bi bi-x-circle-fill me-2 fs-4"></i>
                                        <div>
                                            <strong>This order has been
                                                cancelled<?= $order['payment_status'] == 'paid' ? '. Refund has been initiated.' : '.' ?></strong>
                                            <div class="small">If you have any questions, please contact support.</div>
                                        </div>
                                    </div>
                                    <!-- Progress Tracker was here - removed per request -->
                                <?php endif; ?>

                                <hr class="my-4 opacity-50">

                                <!-- Items -->
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($order['items'] as $item):
                                        $item_status = strtolower($item['status']);
                                        $is_item_cancelled = ($item_status == 'cancelled');
                                        $is_item_delivered = ($item_status == 'delivered' || $order['status'] == 'delivered');
                                        $is_item_shipped = ($item_status == 'shipped' || $order['status'] == 'shipped');

                                        // Logic: Can cancel if item not cancelled/delivered/shipped AND order not shipped/delivered/cancelled?
                                        $can_cancel = (!$is_item_cancelled && !$is_item_delivered && !$is_item_shipped && !in_array($order['status'], ['shipped', 'delivered', 'cancelled']));

                                        // Determine Badge
                                        $item_badge = '';
                                        if ($is_item_cancelled) {
                                            if ($order['payment_status'] == 'paid' || $is_paid) {
                                                $item_badge = '<span class="badge bg-danger">Refund Processing</span>';
                                            } else {
                                                $item_badge = '<span class="badge bg-danger">Cancelled</span>';
                                            }
                                        } elseif ($is_item_delivered) {
                                            $item_badge = '<span class="badge bg-success">Delivered</span>';
                                        } elseif ($is_item_shipped) {
                                            $item_badge = '<span class="badge bg-info">Shipped</span>';
                                        } elseif ($item_status == 'paid') {
                                            $item_badge = '<span class="badge bg-success">Paid</span>';
                                        } else {
                                            $item_badge = ''; // Don't show Pending badge
                                        }
                                        ?>
                                        <div class="d-flex align-items-center gap-3">
                                            <a href="javascript:void(0)" onclick="openQuickView(<?= $item['product_id'] ?>)"
                                                class="flex-shrink-0">
                                                <div class="bg-light rounded p-2 border">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>"
                                                        alt="<?= htmlspecialchars($item['name']) ?>" class="img-fluid"
                                                        style="width: 60px; height: 60px; object-fit: contain;">
                                                </div>
                                            </a>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($item['name']) ?>
                                                            <?= $item_badge ?>
                                                        </h6>
                                                        <p class="mb-1 text-muted small">Quantity: <?= $item['quantity'] ?></p>
                                                    </div>
                                                    <div class="fw-bold text-dark">₹<?= number_format($item['price'], 2) ?></div>
                                                </div>
                                                <!-- Item Action Buttons -->
                                                <div class="mt-2">
                                                    <?php if ($can_cancel): ?>
                                                        <button onclick="cancelItem(<?= $order['id'] ?>, <?= $item['item_id'] ?>)"
                                                            class="btn btn-sm btn-outline-danger py-0 px-2 small fw-bold"
                                                            style="font-size: 0.75rem;">
                                                            Cancel Item
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold fs-5">
                                                    ₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                                <?php if ($is_item_cancelled): ?>
                                                    <div class="text-danger small"><i class="bi bi-x-circle-fill"
                                                            style="font-size: 8px;"></i> Cancelled</div>
                                                <?php elseif ($is_item_delivered): ?>
                                                    <div class="text-success small"><i class="bi bi-check-circle-fill"
                                                            style="font-size: 8px;"></i> Delivered</div>
                                                <?php elseif ($is_item_shipped): ?>
                                                    <div class="text-info small"><i class="bi bi-truck" style="font-size: 8px;"></i>
                                                        Shipped</div>
                                                <?php else: ?>
                                                    <div class="text-warning small"><i class="bi bi-clock-fill"
                                                            style="font-size: 8px;"></i>
                                                        <?= ($item_status == 'pending' ? 'Processing' : ucfirst($item_status)) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Card Footer -->
                            <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Need help? <a href="contact.php"
                                        class="text-primary text-decoration-none">Contact Us</a>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-muted text-uppercase small fw-bold">Total Amount</div>
                                    <div class="fw-bold fs-4 text-dark">₹<?= number_format($order['total'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }

    .transition-all {
        transition: all 0.3s ease;
    }

    .last-border-none:last-child {
        border-bottom: none !important;
    }

    .spacing-1 {
        letter-spacing: 1px;
    }

    .hover-link:hover {
        text-decoration: underline !important;
        color: #0d6efd !important;
    }
</style>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-danger">Cancel Order?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-warning display-4 mb-3 d-block"></i>
                <p class="mb-0 text-muted">Are you sure you want to cancel this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">No, Keep It</button>
                <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmCancelBtn">Yes, Cancel
                    Order</button>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>

<script>
    let orderIdToCancel = null;
    let itemIdToCancel = null;
    let cancelModal = null; // Lazy init

    function cancelItem(orderId, itemId) {
        if (!cancelModal) {
            cancelModal = new bootstrap.Modal(document.getElementById('cancelConfirmModal'));
        }
        orderIdToCancel = orderId;
        itemIdToCancel = itemId;

        // Update Modal Text
        document.querySelector('#cancelConfirmModal .modal-title').innerText = 'Cancel Item?';
        document.querySelector('#cancelConfirmModal .modal-body p').innerText = 'Are you sure you want to cancel this item? This action cannot be undone.';
        document.querySelector('#confirmCancelBtn').innerText = 'Yes, Cancel Item';

        cancelModal.show();
    }

    document.getElementById('confirmCancelBtn').addEventListener('click', function () {
        if (!orderIdToCancel || !itemIdToCancel) return;

        const btn = this;
        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelling...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'cancel_item');
        formData.append('order_id', orderIdToCancel);
        formData.append('item_id', itemIdToCancel);

        fetch('includes/order_action.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                    cancelModal.hide();
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred');
                btn.innerText = originalText;
                btn.disabled = false;
                cancelModal.hide();
            });
    });
</script>