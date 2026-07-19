<?php
include 'includes/auth_check.php';
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$product) {
    header("Location: shop.php");
    exit();
}

include 'includes/header.php';
?>

<style>
    body {
        background-color: #ffffff !important;
    }
</style>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="bg-light rounded p-5 text-center">
                    <img src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid"
                        style="max-height: 400px; object-fit: contain;">
                </div>
            </div>
            <div class="col-md-6">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="shop.php"
                                class="text-secondary text-decoration-none">Shop</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($product['name']) ?>
                        </li>
                    </ol>
                </nav>
                <h1 class="display-5 fw-bold mb-3">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                <h3 class="text-muted mb-4">₹
                    <?= number_format($product['price'], 2) ?>
                </h3>
                <p class="lead mb-4">
                    <?= htmlspecialchars($product['description']) ?>
                </p>

                <!-- Buttons Section -->
                <button class="btn btn-lg w-100 mb-3 fw-bold text-white"
                    style="background-color: #f39c12; border: none; border-radius: 0;"
                    onclick="buyNow(<?= $product['id'] ?>)">
                    Buy Now <i class="bi bi-lightning-fill"></i>
                </button>

                <div class="d-flex gap-2">
                    <button class="btn btn-lg flex-grow-1 fw-bold text-dark"
                        style="background-color: #f1c40f; border: none; border-radius: 0;"
                        onclick="addToCart(<?= $product['id'] ?>)">
                        Add to Cart
                    </button>
                    <button class="btn btn-lg flex-grow-1 fw-bold text-danger bg-white"
                        style="border: 2px solid #dc3545; border-radius: 0;"
                        onclick="addToWishlist(<?= $product['id'] ?>, this)">
                        Wishlist <i class="bi bi-heart-fill"></i>
                    </button>
                </div>

                <hr class="my-5">

                <div class="row">
                    <div class="col-4">
                        <h6 class="fw-bold">Fast Delivery</h6>
                        <p class="small text-muted">2-3 business days</p>
                    </div>
                    <div class="col-4">
                        <h6 class="fw-bold">Free Returns</h6>
                        <p class="small text-muted">Within 30 days</p>
                    </div>
                    <div class="col-4">
                        <h6 class="fw-bold">Warranty</h6>
                        <p class="small text-muted">1 Year Included</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<section class="py-5 bg-light">
    <div class="container">
        <h3 class="fw-bold mb-4">You Might Also Like</h3>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php
            // Fetch 4 random related products
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
            $stmt->execute([$id]);
            $related = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($related as $p):
                ?>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="p-4 text-center bg-white rounded-top">
                            <a href="product.php?id=<?= $p['id'] ?>">
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                                    class="img-fluid" style="height: 150px; object-fit: contain;">
                            </a>
                        </div>
                        <div class="card-body text-center">
                            <h6 class="fw-bold mb-0"><a href="product.php?id=<?= $p['id'] ?>"
                                    class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a></h6>
                            <p class="text-muted small mt-1">₹
                                <?= number_format($p['price'], 2) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>