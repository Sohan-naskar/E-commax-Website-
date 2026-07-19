<?php
include 'includes/auth_check.php';
include 'includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="card border-0 shadow-sm p-5 mx-auto" style="max-width: 600px;">
        <div class="mb-4 text-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor"
                class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </svg>
        </div>
        <h1 class="display-5 fw-bold mb-3">Order Placed!</h1>
        <p class="lead text-muted">Thank you for your purchase. Your order #
            <?= htmlspecialchars($_GET['id']) ?> has been confirmed.
        </p>
        <div class="mt-4">
            <a href="shop.php" class="btn btn-dark btn-lg px-5">Continue Shopping</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>