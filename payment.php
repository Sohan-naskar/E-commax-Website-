<?php
// payment.php
include 'includes/auth_check.php';
include 'includes/header.php';

if (empty($_SESSION['cart']) || empty($_SESSION['checkout_data'])) {
    header("Location: cart.php");
    exit();
}

$total_amount = $_SESSION['checkout_data']['total_amount'];
?>

<div class="container py-5" style="max-width: 600px;">

    <!-- Step Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-uppercase bg-primary text-white p-3 w-100 rounded-top">Payment Gateway</h5>
    </div>

    <div class="bg-white p-4 shadow-sm mb-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <span class="text-muted">Total Amount</span>
            <span class="fw-bold h5 mb-0">₹
                <?= number_format($total_amount) ?>
            </span>
        </div>

        <form action="includes/place_order.php" method="POST" id="paymentForm">

            <h6 class="fw-bold mb-3">UPI</h6>

            <!-- Paytm -->
            <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment_method" value="paytm" id="paytm">
                <label class="form-check-label w-100 d-flex justify-content-between" for="paytm">
                    <span>Paytm</span>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/24/Paytm_Logo_%28standalone%29.svg"
                        alt="Paytm" style="height: 20px;">
                </label>
            </div>

            <!-- Google Pay -->
            <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment_method" value="gpay" id="gpay">
                <label class="form-check-label w-100 d-flex justify-content-between" for="gpay">
                    <span>Google Pay</span>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="GPay"
                        style="height: 20px;">
                </label>
            </div>

            <!-- Add New UPI ID -->
            <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment_method" value="upi_id" id="upi_id"
                    onchange="toggleUpiInput()">
                <label class="form-check-label w-100" for="upi_id">
                    Add new UPI ID
                </label>
                <div class="mt-3 d-none" id="upiInputBox">
                    <input type="text" class="form-control mb-2" name="upi_vpa"
                        placeholder="Enter UPI ID (e.g. mobile@upl)">
                    <button type="button" class="btn btn-warning btn-sm text-white fw-bold">VERIFY</button>
                    <small class="text-muted d-block mt-1">Cash on Delivery option available.</small>
                </div>
            </div>

            <hr>

            <!-- Cash on Delivery -->
            <h6 class="fw-bold mb-3 mt-4">More Options</h6>
            <div class="form-check mb-3 p-3 border rounded">
                <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod">
                <label class="form-check-label w-100 d-flex justify-content-between" for="cod">
                    <span>Cash on Delivery</span>
                </label>
            </div>

            <button type="submit" class="btn btn-warning w-100 fw-bold text-white py-3 text-uppercase mt-3"
                style="background-color: #fb641b;">
                Place Order
            </button>
        </form>
    </div>
</div>

</div>

<!-- Payment Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> Action Required
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fa-solid fa-hand text-warning display-1 mb-3"></i>
                <h5 class="fw-bold text-dark" id="validationMessage">Processing...</h5>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function toggleUpiInput() {
        const upiRadio = document.getElementById('upi_id');
        const inputBox = document.getElementById('upiInputBox');

        if (upiRadio.checked) {
            inputBox.classList.remove('d-none');
        } else {
            inputBox.classList.add('d-none');
        }
    }

    // Add event listeners to other radios to hide UPI box
    const radios = document.querySelectorAll('input[name="payment_method"]');
    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value !== 'upi_id') {
                document.getElementById('upiInputBox').classList.add('d-none');
            } else {
                // If it IS upi_id, make sure the box is visible (though toggleUpiInput handles the logic mostly)
                document.getElementById('upiInputBox').classList.remove('d-none');
            }
        });
    });

    // Validate Form Submission
    document.getElementById('paymentForm').addEventListener('submit', function (event) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        const modal = new bootstrap.Modal(document.getElementById('validationModal'));
        const msgEl = document.getElementById('validationMessage');

        if (!selectedMethod) {
            event.preventDefault(); // Stop form submission
            msgEl.innerText = 'Please select a payment method to proceed.';
            modal.show();
            return;
        }

        // Additional validation for "Add new UPI ID"
        if (selectedMethod.value === 'upi_id') {
            const upiInput = document.querySelector('input[name="upi_vpa"]');
            if (!upiInput.value.trim()) {
                event.preventDefault();
                msgEl.innerText = 'Please enter a valid UPI ID (VPA).';
                modal.show();
            }
        }
    });
</script>