<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// Fetch featured products (limit to 8 for a grid)
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 8");
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured = [];
}

// Fetch user's wishlist product IDs
$wishlist_product_ids = [];
if (isset($_SESSION['user_id'])) {
    $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE customer_id = ?");
    $w_stmt->execute([$_SESSION['user_id']]);
    $wishlist_product_ids = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Check for Missing Address (Pop-up Logic)
$show_address_modal = false;
if (isset($_SESSION['user_id'])) {
    // 1. Check main profile address
    $stmt = $pdo->prepare("SELECT address FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $main_addr = $stmt->fetchColumn();

    // 2. Check saved addresses table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_addresses WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $saved_count = $stmt->fetchColumn();

    // If both are empty, show modal
    if (empty(trim($main_addr)) && $saved_count == 0) {
        $show_address_modal = true;
    }
}
?>

<!-- Hero Section -->
<!-- Hero Section (Full Width Blue) -->
<!-- Hero Carousel -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"
            aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">

        <!-- Slide 1: Gaming (Blue) -->
        <div class="carousel-item active">
            <div class="container-fluid bg-primary position-relative overflow-hidden hero-section"
                style="min-height: 500px; padding-top: 40px;">
                <!-- Decorative Elements -->
                <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-white opacity-10"
                    style="width: 400px; height: 400px; filter: blur(80px);"></div>
                <div class="position-absolute bottom-0 end-0 translate-middle-x rounded-circle bg-primary-dark opacity-25"
                    style="width: 300px; height: 300px; filter: blur(60px);"></div>

                <div class="container h-100 position-relative z-1">
                    <div class="row h-100 align-items-center">
                        <div class="col-lg-6 text-white py-5">
                            <span class="d-block text-uppercase letter-spacing-2 mb-2 opacity-75 fw-bold"
                                style="font-size: 0.9rem; letter-spacing: 2px;">Dualsense Wireless Controller</span>
                            <h1 class="display-3 fw-bold mb-3 d-none d-md-block"
                                style="font-family: 'Poppins', sans-serif; line-height: 1.1;">Bring Gaming <br> Worlds
                                To Life</h1>
                            <h1 class="display-4 fw-bold mb-3 d-md-none" style="font-family: 'Poppins', sans-serif;">
                                Bring Gaming Worlds To Life</h1>
                            <div class="d-flex align-items-baseline gap-2 mb-4">
                                <span class="fs-5 opacity-75">Starting at</span>
                                <span class="display-6 fw-bold text-warning">₹449.99</span>
                            </div>
                            <a href="shop.php?cat=audio"
                                class="btn btn-light rounded-1 px-5 py-3 fw-bold text-primary shadow-lg hover-scale">
                                Shop Now <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="col-lg-6 position-relative text-center">
                            <img src="assets/images/headphone_image.png" alt="Headphones"
                                class="img-fluid hero-image position-relative z-2"
                                style="max-height: 450px; transform: rotate(-15deg);">
                            <div class="position-absolute top-0 end-0 bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-lg animate-float"
                                style="width: 100px; height: 100px; right: 10% !important; top: 10% !important; z-index: 3;">
                                <div class="text-center lh-1">
                                    <span class="d-block small">Only</span>
                                    <span class="fs-4">₹449</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 2: Valentine's Sale (Red) -->
        <div class="carousel-item">
            <div class="container-fluid position-relative overflow-hidden hero-section"
                style="background-color: #dc3545; min-height: 500px; padding-top: 40px;">
                <!-- <div class="position-absolute top-50 start-50 translate-middle rounded-circle bg-white opacity-10"
                    style="width: 600px; height: 600px; filter: blur(100px);"></div> -->
                <div class="container h-100 position-relative z-1">
                    <div class="row h-100 align-items-center">
                        <div class="col-lg-6 text-white py-5">
                            <span class="d-block text-uppercase letter-spacing-2 mb-2 opacity-75 fw-bold"
                                style="font-size: 0.9rem; letter-spacing: 2px;">Valentine's Special</span>
                            <h1 class="display-3 fw-bold mb-3"
                                style="font-family: 'Poppins', sans-serif; line-height: 1.1;">Gift Love, <br> Gift Tech
                            </h1>
                            <div class="d-flex align-items-baseline gap-2 mb-4">
                                <span class="fs-4 fw-bold">Min. 70% Off</span>
                                <span class="fs-5 opacity-75">on selected items</span>
                            </div>
                            <a href="shop.php"
                                class="btn btn-light rounded-1 px-5 py-3 fw-bold text-danger shadow-lg hover-scale">
                                Shop Gifts <i class="bi bi-heart-fill ms-2"></i>
                            </a>
                        </div>
                        <div class="col-lg-6 position-relative text-center">
                            <!-- Placeholder for Valentine Image -->
                            <img src="assets/images/couple_Watch_transparent.png" alt="Couple Watch"
                                class="img-fluid hero-image position-relative z-2"
                                style="width: 450px; height: 430px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 3: Future Tech (Dark) -->
        <div class="carousel-item">
            <div class="container-fluid bg-dark position-relative overflow-hidden hero-section"
                style="min-height: 500px; padding-top: 40px;">
                <div class="position-absolute bottom-0 start-0 rounded-circle bg-primary opacity-25"
                    style="width: 500px; height: 500px; filter: blur(120px);"></div>
                <div class="container h-100 position-relative z-1">
                    <div class="row h-100 align-items-center">
                        <div class="col-lg-6 text-white py-5">
                            <span class="d-block text-uppercase letter-spacing-2 mb-2 opacity-75 fw-bold text-info"
                                style="font-size: 0.9rem; letter-spacing: 2px;">New Arrivals</span>
                            <h1 class="display-3 fw-bold mb-3"
                                style="font-family: 'Poppins', sans-serif; line-height: 1.1;">Experience The <br> Future
                                Today</h1>
                            <p class="lead mb-4 opacity-75">Discover our latest collection of smart gadgets and
                                accessories.</p>
                            <a href="shop.php?cat=smartphone"
                                class="btn btn-info rounded-1 px-5 py-3 fw-bold text-dark shadow-lg hover-scale">
                                Explore Now <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="col-lg-6 position-relative text-center">
                            <!-- Placeholder for Tech Image -->
                            <img src="assets/images/laptop_transparent_new.png" alt="New Laptop"
                                class="img-fluid hero-image position-relative z-2"
                                style="width: 380px; height: 300px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Navigation Buttons -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"
        style="width: 50px; height: 100px; background-color: white; top: 50%; transform: translateY(-50%); opacity: 1; border-radius: 0 5px 5px 0; left: 0;">
        <span class="carousel-control-prev-icon" aria-hidden="true"
            style="filter: invert(1); width: 20px; height: 20px;"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"
        style="width: 50px; height: 100px; background-color: white; top: 50%; transform: translateY(-50%); opacity: 1; border-radius: 5px 0 0 5px; right: 0;">
        <span class="carousel-control-next-icon" aria-hidden="true"
            style="filter: invert(1); width: 20px; height: 20px;"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>



<!-- Category Strip (Circular) -->
<div class="container my-5">
    <div class="row g-4 justify-content-center">
        <?php
        $categories = [
            ['name' => 'Mobile & Tablet', 'icon' => 'bi-phone', 'link' => 'shop.php?cat=smartphone'],
            ['name' => 'Smartwatches', 'icon' => 'bi-smartwatch', 'link' => 'shop.php?cat=watch'],
            ['name' => 'Audio & Headphones', 'icon' => 'bi-headphones', 'link' => 'shop.php?cat=audio'],
            ['name' => 'Camera', 'icon' => 'bi-camera', 'link' => 'shop.php?cat=camera'],
            ['name' => 'Laptops', 'icon' => 'bi-laptop', 'link' => 'shop.php?cat=laptop'],
            ['name' => 'Gaming & Accessories', 'icon' => 'bi-controller', 'link' => 'shop.php?cat=accessories'],
            ['name' => 'Home Appliance', 'icon' => 'bi-house', 'link' => 'shop.php?cat=home-appliance']
        ];
        foreach ($categories as $cat): ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-auto">
                <a href="<?= $cat['link'] ?>"
                    class="text-decoration-none text-dark d-flex flex-column align-items-center gap-2 category-item">
                    <div class="category-circle bg-white shadow-sm rounded-circle d-flex align-items-center justify-content-center transition-all"
                        style="width: 100px; height: 100px; border: 1px solid #f0f0f0;">
                        <i class="bi <?= $cat['icon'] ?> fs-2 text-muted"></i>
                    </div>
                    <span class="fw-semibold small text-center"><?= $cat['name'] ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Promotional Banners Grid -->
<div class="container mb-5">
    <div class="row g-4">
        <!-- Banner 1: Pink/Purple -->
        <div class="col-md-4">
            <div class="p-4 rounded-3 h-100 position-relative overflow-hidden text-white d-flex flex-column justify-content-center"
                style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%); min-height: 200px;">
                <span class="badge bg-white text-danger mb-2 align-self-start">Save Up To 50%</span>
                <h3 class="fw-bold mb-1 text-dark">Gaming<br>Consoles</h3>
                <a href="shop.php?cat=accessories"
                    class="text-dark fw-bold text-decoration-none mt-3 stretched-link">Shop Now <i
                        class="bi bi-arrow-right"></i></a>
                <!-- Decorative Circle -->
                <div class="position-absolute bottom-0 end-0 translate-middle-y me-n3 opacity-50">
                    <i class="bi bi-controller" style="font-size: 8rem; color: rgba(255,255,255,0.4);"></i>
                </div>
            </div>
        </div>

        <!-- Banner 2: Green/Blue -->
        <div class="col-md-4">
            <div class="p-4 rounded-3 h-100 position-relative overflow-hidden text-white d-flex flex-column justify-content-center"
                style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); min-height: 200px;">
                <span class="badge bg-white text-primary mb-2 align-self-start">New Arrival</span>
                <h3 class="fw-bold mb-1 text-dark">Sound<br>Experience</h3>
                <a href="shop.php?cat=audio" class="text-dark fw-bold text-decoration-none mt-3 stretched-link">Shop Now
                    <i class="bi bi-arrow-right"></i></a>
                <!-- Decorative Icon -->
                <div class="position-absolute bottom-0 end-0 translate-middle-y me-n3 opacity-50">
                    <i class="bi bi-speaker" style="font-size: 8rem; color: rgba(255,255,255,0.4);"></i>
                </div>
            </div>
        </div>

        <!-- Banner 3: Orange/Yellow -->
        <div class="col-md-4">
            <div class="p-4 rounded-3 h-100 position-relative overflow-hidden text-white d-flex flex-column justify-content-center"
                style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); min-height: 200px;">
                <span class="badge bg-white text-warning mb-2 align-self-start">Top Seller</span>
                <h3 class="fw-bold mb-1 text-dark">Smart<br>Wearables</h3>
                <a href="shop.php?cat=watch" class="text-dark fw-bold text-decoration-none mt-3 stretched-link">Shop Now
                    <i class="bi bi-arrow-right"></i></a>
                <!-- Decorative Icon -->
                <div class="position-absolute bottom-0 end-0 translate-middle-y me-n3 opacity-50">
                    <i class="bi bi-smartwatch" style="font-size: 8rem; color: rgba(255,255,255,0.4);"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trending Products Section -->
<section class="py-5 bg-light-gradient">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="mb-0 fw-bold">Trending Products</h2>
                <p class="text-muted small">Latest additions to our collection</p>
            </div>
            <a href="shop.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">View All</a>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($featured as $product): ?>
                <div class="col">
                    <div class="product-card h-100">
                        <div class="product-img-wrapper">
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
                            <span class="product-category">Electronics</span>
                            <h5 class="product-title">
                                <a href="product.php?id=<?= $product['id'] ?>"
                                    class="text-decoration-none text-dark"><?= htmlspecialchars($product['name']) ?></a>
                            </h5>
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <span class="product-price">₹<?= number_format($product['price'], 2) ?></span>
                                <span
                                    class="text-muted text-decoration-line-through small">₹<?= number_format($product['price'] * 1.1, 2) ?></span>
                            </div>

                        </div>
                        <div class="p-3 pt-0">
                            <a href="javascript:void(0)" onclick="addToCart(<?= $product['id'] ?>)"
                                class="btn btn-primary w-100 rounded-pill btn-sm">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Banner Grid (Optional visual break) -->
<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="p-4 rounded-xl h-100 position-relative overflow-hidden shadow-soft"
                style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                <h4 class="fw-bold mb-2 text-dark">Macbook Pro 16</h4>
                <p class="text-muted small mb-3">2K Fullview Display</p>
                <img src="assets/images/laptop_placeholder.svg" onerror="this.style.display='none'"
                    class="img-fluid position-absolute bottom-0 end-0"
                    style="width: 80%; transform: rotate(-10deg) translate(10%, 10%);">
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 rounded-xl h-100 position-relative overflow-hidden shadow-soft"
                style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);">
                <h4 class="fw-bold mb-2 text-dark">Premium Headphones</h4>
                <p class="text-muted small mb-3">High-Fidelity Audio</p>
                <img src="assets/images/headphone_placeholder.svg" onerror="this.style.display='none'"
                    class="img-fluid position-absolute bottom-0 end-0"
                    style="width: 60%; transform: translate(10%, 10%);">
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 rounded-xl h-100 position-relative overflow-hidden shadow-soft"
                style="background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);">
                <h4 class="fw-bold mb-2 text-dark">Bamboo Plant</h4>
                <p class="text-muted small mb-3">Eco Friendly</p>
                <img src="assets/images/bamboo_placeholder.svg" onerror="this.style.display='none'"
                    class="img-fluid position-absolute bottom-0 end-0"
                    style="width: 70%; transform: translate(10%, 10%);">
            </div>
        </div>
    </div>
</div>

<!-- Newsletter -->
<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon"><i class="bi bi-truck"></i></div>
                <div class="feature-title">FREE DELIVERY</div>
                <div class="feature-desc">For all orders over ₹100</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                <div class="feature-title">SECURE PAYMENT</div>
                <div class="feature-desc">100% secure payment</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon"><i class="bi bi-umbrella"></i></div>
                <div class="feature-title">1 YEAR WARRANTY</div>
                <div class="feature-desc">Support for manufacturing defects</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon"><i class="bi bi-headset"></i></div>
                <div class="feature-title">SUPPORT 24/7</div>
                <div class="feature-desc">We are available 24 hours a day</div>
            </div>
        </div>
    </div>
</div>

<!-- Newsletter -->
<section class="py-5">
    <div class="container">
        <div class="p-5 text-center text-white position-relative overflow-hidden"
            style="background-color: blue; border: 4px solid yellow; border-radius: 1rem;">
            <div class="position-relative z-2">
                <h2 class="display-6 fw-bold mb-3">Subscribe to our Newsletter</h2>
                <p class="mb-4 opacity-75">Get latest updates, offers and 15% discount on your first order</p>
                <form action="#" class="mx-auto d-flex gap-2 justify-content-center" style="max-width: 500px;">
                    <input type="email" class="form-control rounded-pill border-0 px-4"
                        placeholder="Enter your email address">
                    <button class="btn btn-dark rounded-pill px-4">Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
    /* Additional specific styles for homepage interactions */
    .hover-action {
        transition: opacity 0.3s ease;
    }

    .product-card:hover .hover-action {
        opacity: 1 !important;
    }
</style>
<!-- Address Missing Modal -->
<?php if ($show_address_modal): ?>
    <div class="modal fade" id="addressMissingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg position-relative overflow-hidden" style="border-radius: 20px;">
                <!-- Decorative Header -->
                <div class="position-absolute top-0 start-0 w-100"
                    style="height: 10px; background: linear-gradient(90deg, #4200FF, #FF0096);"></div>

                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-geo-alt-fill text-primary" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-3">Delivery Address Missing!</h3>
                    <p class="text-muted mb-4">It looks like you haven't added a delivery address yet. Please add one to
                        ensure smooth delivery of your orders.</p>

                    <a href="profile.php?tab=addresses&open_add=1"
                        class="btn btn-primary btn-lg rounded-pill px-5 fw-bold w-100 shadow-sm">
                        ADD ADDRESS
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Debug Info (View Source to check) -->
<!-- 
Debug Address Check:
User ID: <?= $_SESSION['user_id'] ?? 'Not Set' ?> 
Main Address: '<?= $main_addr ?? 'N/A' ?>'
Saved Count: <?= $saved_count ?? 'N/A' ?> 
Show Modal: <?= $show_address_modal ? 'TRUE' : 'FALSE' ?> 
-->

<?php include 'includes/footer.php'; ?>

<?php if ($show_address_modal): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var myModalElement = document.getElementById('addressMissingModal');
            if (myModalElement) {
                var myModal = new bootstrap.Modal(myModalElement);
                myModal.show();
            } else {
                console.error("Bootstrap Modal Element missing");
            }
        });
    </script>
<?php endif; ?>