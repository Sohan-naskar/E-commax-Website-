<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// Fetch products from database
$category_filter = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page_title = "Shop All";

try {
    if ($search_query) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC");
        $term = "%$search_query%";
        $stmt->execute([$term, $term]);
        $page_title = "Search Results for \"" . htmlspecialchars($search_query) . "\"";
    } elseif ($category_filter) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
        $stmt->execute([$category_filter]);
        $page_title = ucfirst($category_filter) . " Collection";
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        $page_title = "Shop All";
    }
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

// Fetch user's wishlist product IDs
$wishlist_product_ids = [];
if (isset($_SESSION['user_id'])) {
    $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE customer_id = ?");
    $w_stmt->execute([$_SESSION['user_id']]);
    $wishlist_product_ids = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<style>
    body {
        background-color: #ffffff;
        min-height: 100vh;
    }
</style>

<section class="py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold"><?= htmlspecialchars($page_title) ?></h1>
        <p class="lead text-muted">Explore our full collection.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($db_products as $product): ?>
                <div class="col">
                    <div class="product-card h-100">
                        <div class="product-img-wrapper position-relative">
                            <span
                                class="badge bg-danger position-absolute top-0 start-0 m-3 shadow-sm rounded-pill">-10%</span>
                            <div
                                class="position-absolute bottom-0 end-0 m-3 d-flex flex-column gap-2 opacity-0 hover-action">
                                <button class="btn btn-light rounded-circle shadow-sm p-2"
                                    onclick="addToWishlist(<?= $product['id'] ?>, this)">
                                    <?php if (in_array($product['id'], $wishlist_product_ids)): ?>
                                        <i class="bi bi-heart-fill text-danger"></i>
                                    <?php else: ?>
                                        <i class="bi bi-heart"></i>
                                    <?php endif; ?>
                                </button>
                                <button class="btn btn-light rounded-circle shadow-sm p-2"
                                    onclick="openQuickView(<?= $product['id'] ?>)">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                </button>
                            </div>
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                            </a>
                        </div>
                        <div class="product-details text-center">
                            <span class="product-category text-uppercase small text-muted">Electronics</span>
                            <h5 class="product-title mt-1">
                                <a href="product.php?id=<?= $product['id'] ?>"
                                    class="text-decoration-none text-dark fw-bold"><?= htmlspecialchars($product['name']) ?></a>
                            </h5>
                            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                                <span
                                    class="product-price text-primary fw-bold">₹<?= number_format($product['price'], 2) ?></span>
                                <span
                                    class="text-muted text-decoration-line-through small">₹<?= number_format($product['price'] * 1.1, 2) ?></span>
                            </div>
                            <a href="javascript:void(0)" onclick="addToCart(<?= $product['id'] ?>)"
                                class="btn btn-primary w-100 rounded-pill">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<?php include 'includes/footer.php'; ?>