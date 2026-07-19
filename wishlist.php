<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// Fetch Users for Sidebar
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch Wishlist Items
$sql = "
    SELECT w.id as wishlist_id, w.created_at,
           p.id as product_id, p.name, p.price, p.image
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.customer_id = ?
    ORDER BY w.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 text-muted fw-bold">My
                            Orders</a>

                        <a href="wishlist.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 text-primary bg-light fw-bold">
                            My Wishlist
                        </a>



                        <div class="border-top text-center p-3">
                            <a href="logout.php" class="text-decoration-none fw-bold text-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-lg-9">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">My Wishlist (
                            <?= count($wishlist_items) ?>)
                        </h5>

                        <?php if (empty($wishlist_items)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-heart text-muted display-1"></i>
                                <h5 class="mt-3 text-muted">Your wishlist is empty</h5>
                                <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($wishlist_items as $item): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="position-relative">
                                                <img src="<?= htmlspecialchars($item['image']) ?>" class="card-img-top p-3"
                                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                                    style="height: 200px; object-fit: contain; width: 100%;">
                                                <button
                                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle"
                                                    onclick="removeFromWishlist(<?= $item['wishlist_id'] ?>)" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold text-truncate">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </h6>
                                                <p class="card-text fw-bold text-primary">₹
                                                    <?= number_format($item['price']) ?>
                                                </p>
                                                <div class="d-grid gap-2 d-md-flex">
                                                    <a href="product.php?id=<?= $item['product_id'] ?>"
                                                        class="btn btn-sm btn-outline-primary flex-grow-1">View Product</a>
                                                    <button class="btn btn-sm btn-light border"
                                                        onclick="openQuickView(<?= $item['product_id'] ?>)" title="Quick View">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function removeFromWishlist(wishlistId) {
        if (confirm('Remove this item from wishlist?')) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('wishlist_id', wishlistId);

            fetch('includes/wishlist_action.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error removing item');
                    }
                });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>