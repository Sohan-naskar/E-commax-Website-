<!-- Footer -->
<footer class="py-5 mt-5" style="background-color: #132440 !important;">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <img src="assets/images/logo.svg" alt="eCommax" height="30" class="mb-3">
                <p class="text-white-50 small">Elevating your lifestyle with premium curated products. Minimalist design
                    for maximum impact.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold text-white">Quick Links</h5>
                <ul class="list-unstyled text-white-50 small">
                    <li><a href="shop.php" class="text-decoration-none text-white-50">Shop</a></li>
                    <li><a href="about.php" class="text-decoration-none text-white-50">About Us</a></li>
                    <li><a href="privacy_policy.php" class="text-decoration-none text-white-50">Privacy Policy</a></li>
                    <li><a href="terms_of_service.php" class="text-decoration-none text-white-50">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold text-white">Newsletter</h5>
                <form action="#" class="d-flex gap-2">
                    <input type="email" class="form-control form-control-sm" placeholder="Your email">
                    <button class="btn btn-primary btn-sm">Subscribe</button>
                </form>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-white-50 small">
            &copy; <?php echo date('Y'); ?> eCommax Store. All rights reserved.
        </div>

    </div>
    </div>
</footer>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-0">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"
                    aria-label="Close"></button>
                <div class="row g-0">
                    <div class="col-md-6 d-flex align-items-center justify-content-center bg-light p-4 rounded-start-4">
                        <img id="qv-image" src="" class="img-fluid object-fit-contain" style="max-height: 300px;"
                            alt="Product Image">
                    </div>
                    <div class="col-md-6 p-4 d-flex flex-column justify-content-center">
                        <span id="qv-category" class="text-uppercase text-muted small fw-bold mb-2">Category</span>
                        <h4 id="qv-title" class="fw-bold mb-2">Product Title</h4>
                        <h3 id="qv-price" class="text-primary fw-bold mb-3">₹0.00</h3>
                        <p id="qv-description" class="text-muted small mb-4">Product description goes here...</p>

                        <div class="d-grid gap-2">
                            <button id="qv-add-to-cart" class="btn btn-primary rounded-pill py-2" onclick="">Add to
                                Cart</button>
                            <a id="qv-view-details" href="#" class="btn btn-outline-dark rounded-pill py-2">View Full
                                Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="liveToast" class="toast align-items-center text-white bg-primary border-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-message">
                Hello, world! This is a toast message.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js?v=<?= time() ?>"></script>
</body>

</html>