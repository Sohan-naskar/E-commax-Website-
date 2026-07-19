<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eCommax | Premium Store</title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon-removebg-preview.png" type="image/png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>

    <!-- Header Wrapper -->
    <header class="header-wrapper sticky-top shadow-sm">



        <!-- 2. Main Header (White Background) -->
        <div class="main-header bg-white py-3">
            <div class="container">
                <div class="row align-items-center g-3">

                    <!-- Logo -->
                    <div class="col-6 col-md-3 text-start">
                        <a class="navbar-brand d-flex align-items-center" href="index.php">
                            <img src="assets/images/logo.svg" alt="eCommax" style="width: 200px; height: 60px;">
                        </a>
                    </div>

                    <!-- Search Bar (Centered) -->
                    <div class="col-12 col-md-6 order-3 order-md-2 d-flex flex-column align-items-center"
                        style="padding: 0 2px !important;">
                        <div class="search-bar-container position-relative">
                            <form action="shop.php" method="GET"
                                class="d-flex position-relative shadow-sm rounded-pill bg-white mx-auto"
                                style="border: 1px solid black; max-width: 600px;">
                                <div class="flex-grow-1 position-relative">
                                    <input type="text" name="search" id="searchInput"
                                        class="form-control border-0 shadow-none h-100 ps-4"
                                        style="border-top-left-radius: 50rem; border-bottom-left-radius: 50rem;"
                                        placeholder="Search for products, brands..." autocomplete="off"
                                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                    <ul id="suggestionList"
                                        class="list-group position-absolute w-100 shadow-lg mt-1 rounded-3 border-0"
                                        style="display:none; z-index: 1050; top: 100%; max-height: 300px; overflow-y: auto;">
                                    </ul>
                                </div>
                                <button class="btn btn-warning text-dark px-4 fw-bold" type="submit"
                                    style="border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Centered Navigation Links (Moved Here) -->
                        <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $is_home = ($current_page == 'index.php');
                        $is_categories = ($current_page == 'shop.php' && isset($_GET['cat']));
                        $is_shop = ($current_page == 'shop.php' && !isset($_GET['cat']));
                        ?>
                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <a class="<?= $is_home ? 'text-primary fw-bold' : 'text-dark fw-medium' ?> text-decoration-none"
                                href="index.php" style="font-size: 0.9rem;">Home</a>
                            <!-- Categories Dropdown -->
                            <div class="department-dropdown dropdown">
                                <a href="#"
                                    class="<?= $is_categories ? 'text-primary fw-bold' : 'text-dark fw-medium' ?> text-decoration-none dropdown-toggle d-flex align-items-center gap-1"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="font-size: 0.9rem;">
                                    Categories
                                </a>
                                <ul class="dropdown-menu shadow-lg mt-2 animate slideIn">
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=smartphone"><i
                                                class="bi bi-phone me-2 text-primary"></i> Mobile & Tablets</a></li>
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=watch"><i
                                                class="bi bi-smartwatch me-2 text-primary"></i> Smart Watches</a></li>
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=audio"><i
                                                class="bi bi-headphones me-2 text-primary"></i> Audio & Headphones</a>
                                    </li>
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=laptop"><i
                                                class="bi bi-laptop me-2 text-primary"></i> Laptop</a></li>
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=camera"><i
                                                class="bi bi-camera me-2 text-primary"></i> Camera</a></li>
                                    <li><a class="dropdown-item py-2 border-bottom" href="shop.php?cat=accessories"><i
                                                class="bi bi-controller me-2 text-primary"></i> Gaming Accessories</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="shop.php?cat=home-appliance"><i
                                                class="bi bi-house-gear me-2 text-primary"></i> Home Appliances</a></li>
                                </ul>
                            </div>
                            <a class="<?= $is_shop ? 'text-primary fw-bold' : 'text-dark fw-medium' ?> text-decoration-none"
                                href="shop.php" style="font-size: 0.9rem;">Shop</a>
                        </div>
                    </div>

                    <!-- User Actions (Right) -->
                    <div class="col-6 col-md-3 order-2 order-md-3">
                        <div class="d-flex align-items-center justify-content-end gap-3 text-dark">

                            <!-- Wishlist -->
                            <a href="wishlist.php"
                                class="text-dark position-relative text-decoration-none d-flex flex-column align-items-center"
                                title="Wishlist">
                                <div class="position-relative">
                                    <i class="bi bi-heart fs-5"></i>
                                    <!-- Optional Badge -->
                                </div>
                                <span class="d-none d-lg-block text-muted" style="font-size: 0.7rem;">Wishlist</span>
                            </a>

                            <!-- Cart -->
                            <a href="cart.php"
                                class="text-dark position-relative text-decoration-none d-flex flex-column align-items-center"
                                title="Cart">
                                <div class="position-relative">
                                    <i class="bi bi-cart3 fs-5"></i>
                                    <span
                                        class="badge position-absolute top-0 start-100 translate-middle bg-warning text-dark rounded-circle border border-white badge-float d-flex justify-content-center align-items-center"
                                        style="font-size: 0.75rem; width: 20px; height: 20px; padding: 0;">
                                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                                    </span>
                                </div>
                                <span class="d-none d-lg-block text-muted" style="font-size: 0.7rem;">Cart</span>
                            </a>

                            <!-- Account -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="dropdown">
                                    <a href="#"
                                        class="text-dark text-decoration-none dropdown-toggle d-flex align-items-center gap-2"
                                        data-bs-toggle="dropdown">
                                        <div class="bg-light p-2 rounded-circle">
                                            <i class="bi bi-person fs-5"></i>
                                        </div>
                                        <div class="d-none d-lg-block text-start lh-1">
                                            <span class="d-block text-muted" style="font-size: 0.7rem;">Account</span>
                                            <span class="fw-bold"
                                                style="font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm animate slideIn">
                                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>
                                                Profile</a></li>
                                        <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam me-2"></i>
                                                Orders</a></li>
                                        <li><a class="dropdown-item" href="contact.php"><i class="bi bi-headset me-2"></i>
                                                24x7 Customer Care</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="logout.php"><i
                                                    class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <a href="login.php" target="_blank"
                                    class="text-white text-decoration-none d-flex align-items-center gap-2">
                                    <div class="bg-white bg-opacity-10 p-2 rounded-circle">
                                        <i class="bi bi-person fs-5"></i>
                                    </div>
                                    <div class="d-none d-lg-block text-start lh-1">
                                        <span class="d-block text-white-50" style="font-size: 0.7rem;">Sign In</span>
                                        <span class="fw-bold" style="font-size: 0.85rem;">Account</span>
                                    </div>
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Navbar (White) -->




        </div>
        </div>
        </div>
    </header>

    <!-- Search Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById('searchInput');
            const suggestionList = document.getElementById('suggestionList');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const query = this.value;
                    console.log('Searching for:', query); // Debug
                    if (query.length > 0) {
                        fetch(`includes/search_suggestions.php?term=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log('Results:', data); // Debug
                                suggestionList.innerHTML = '';
                                suggestionList.style.display = 'block'; if (data.length > 0) {
                                    data.forEach(item => {
                                        const li = document.createElement('li');
                                        li.className = 'list-group-item list-group-item-action cursor-pointer d-flex align-items-center gap-2 border-0 border-bottom';
                                        li.innerHTML = `
                                            <img src="${item.image}" alt="${item.name}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="small fw-bold text-dark text-truncate" style="max-width: 200px;">${item.name}</div>
                                                <div class="small text-muted">₹${item.price} - ${item.category}</div>
                                            </div>
                                        `;
                                        li.onclick = () => {
                                            searchInput.value = item.name;
                                            window.location.href = `product.php?id=${item.id}`;
                                        };
                                        suggestionList.appendChild(li);
                                    });
                                } else {
                                    suggestionList.innerHTML = '<li class="list-group-item text-muted small">No products found</li>';
                                }
                            })
                            .catch(err => {
                                console.error('Error fetching suggestions:', err);
                                suggestionList.style.display = 'none';
                            });
                    } else {
                        suggestionList.style.display = 'none';
                    }
                });

                document.addEventListener('click', function (e) {
                    if (!searchInput.contains(e.target) && !suggestionList.contains(e.target)) {
                        suggestionList.style.display = 'none';
                    }
                });
            }
        });
    </script>