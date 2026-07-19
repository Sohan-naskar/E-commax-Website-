<?php
include 'includes/auth_check.php';
include 'includes/header.php';
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;

// Fetch current user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Saved Addresses
$stmt_addr = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC");
$stmt_addr->execute([$user_id]);
$addresses = $stmt_addr->fetchAll();
?>

<section class="py-2 bg-light">
    <div class="container text-center">
        <h1 class="h3 fw-bold m-0">Your Cart</h1>
        <p class="small text-muted m-0">Review your selected items.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (empty($cart)): ?>
            <div class="text-center py-5">
                <h3 class="text-muted">Your cart is empty.</h3>
                <a href="shop.php" class="btn btn-dark mt-3">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart as $item):
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $total += $subtotal;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="img"
                                                        style="width: 70px; height: 70px; object-fit: contain;"
                                                        class="rounded me-3 border">
                                                    <span>
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>₹
                                                <?= number_format($item['price'], 2) ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-light rounded-circle border"
                                                        onclick="updateCartQty(<?= $item['id'] ?>, -1)"><i
                                                            class="bi bi-dash"></i></button>
                                                    <input type="text" class="form-control form-control-sm text-center p-0"
                                                        value="<?= $item['quantity'] ?>" style="width: 40px;" readonly
                                                        id="qty_cart_<?= $item['id'] ?>">
                                                    <button type="button" class="btn btn-sm btn-light rounded-circle border"
                                                        onclick="updateCartQty(<?= $item['id'] ?>, 1)"><i
                                                            class="bi bi-plus"></i></button>
                                                </div>
                                            </td>
                                            <td>₹
                                                <?= number_format($subtotal, 2) ?>
                                            </td>
                                            <td>
                                                <a href="includes/cart_action.php?action=remove&id=<?= $item['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger">Remove</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <!-- ORDER SUMMARY SECTION (Moved into Left Column) -->
                <div class="mt-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h4 class="mb-3">Order Summary</h4>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span class="fw-bold">₹
                                    <?= number_format($total, 2) ?>
                                </span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="h5">Total</span>
                                <span class="h5 fw-bold">₹
                                    <?= number_format($total, 2) ?>
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-dark flex-grow-1 py-2" onclick="document.getElementById('addressSelectionForm').submit()">Proceed to Checkout</button>
                                <a href="includes/cart_action.php?action=clear" class="btn btn-outline-secondary py-2">Clear Cart</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- ADDRESS SELECTION SECTION (Sidebar) -->
                <form action="includes/process_checkout.php" method="POST" id="addressSelectionForm">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-primary text-white text-uppercase fw-bold">
                            Select Delivery Address
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-3 fw-bold" onclick="toggleNewAddress()">
                                <i class="bi bi-plus-lg me-1"></i> Add New Address
                            </button>

                            <?php if (!empty($addresses)): ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($addresses as $index => $addr): ?>
                                    <div class="card shadow-sm border-0 mb-3 address-card bg-light">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-start gap-2">
                                                <input type="radio" name="address_id" value="<?= $addr['id'] ?>" id="addr_<?= $addr['id'] ?>"
                                                    class="mt-1 form-check-input address-radio" <?= $index === 0 ? 'checked' : '' ?>>
                                                <label for="addr_<?= $addr['id'] ?>" class="w-100 cursor-pointer">
                                                    <div class="mb-1">
                                                        <span class="fw-bold d-block"><?= htmlspecialchars($addr['name']) ?></span>
                                                        <span class="badge bg-white text-secondary border"><?= htmlspecialchars($addr['address_type']) ?></span>
                                                        <button type="button" class="btn btn-sm text-primary fw-bold p-0 float-end" onclick='editAddress(<?= json_encode($addr) ?>)'>EDIT</button>
                                                    </div>
                                                    <div class="text-muted small lh-sm">
                                                        <?= htmlspecialchars($addr['address']) ?>,
                                                        <?= htmlspecialchars($addr['city']) ?> - <span class="fw-bold"><?= htmlspecialchars($addr['pincode']) ?></span>
                                                        <div class="mt-1 fw-bold"><?= htmlspecialchars($addr['phone']) ?></div>
                                                    </div>
                                                </label>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center small">No saved addresses.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const radios = document.querySelectorAll('.address-radio');
                        radios.forEach(radio => {
                            radio.addEventListener('change', function() {
                                // Hide all deliver buttons
                                document.querySelectorAll('.deliver-btn').forEach(btn => btn.style.display = 'none');
                                // Show the one in this card
                                const cardBody = this.closest('.card-body');
                                if (cardBody) {
                                    const btn = cardBody.querySelector('.deliver-btn');
                                    if (btn) btn.style.display = 'block';
                                }
                            });
                        });
                    });
                </script>
                    </div>
                </form>


            </div>
            </div>
        <?php endif; ?>
    </div>
</section>


<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary text-uppercase" id="formTitle"><i class="bi bi-plus-circle me-2"></i> Add Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="includes/process_checkout.php" method="POST" id="checkoutAddressForm">
                    <input type="hidden" name="action" id="formAction" value="new_address">
                    <input type="hidden" name="address_id" id="address_id" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <input type="text" class="form-control" name="name" placeholder="Name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control" name="phone" placeholder="Mobile Number (10 digits)" value="<?= htmlspecialchars($user['phone']) ?>" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control" name="pincode" placeholder="Pincode" required>
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control" name="city" placeholder="City" required>
                        </div>
                        <div class="col-12">
                            <textarea class="form-control" name="address" placeholder="Address" style="height: 80px" required><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <select class="form-select" name="state" required>
                                <option value="" selected disabled>Select State</option>
                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                <option value="Assam">Assam</option>
                                <option value="Bihar">Bihar</option>
                                <option value="Chhattisgarh">Chhattisgarh</option>
                                <option value="Goa">Goa</option>
                                <option value="Gujarat">Gujarat</option>
                                <option value="Haryana">Haryana</option>
                                <option value="Himachal Pradesh">Himachal Pradesh</option>
                                <option value="Jharkhand">Jharkhand</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Kerala">Kerala</option>
                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="Manipur">Manipur</option>
                                <option value="Meghalaya">Meghalaya</option>
                                <option value="Mizoram">Mizoram</option>
                                <option value="Nagaland">Nagaland</option>
                                <option value="Odisha">Odisha</option>
                                <option value="Punjab">Punjab</option>
                                <option value="Rajasthan">Rajasthan</option>
                                <option value="Sikkim">Sikkim</option>
                                <option value="Tamil Nadu">Tamil Nadu</option>
                                <option value="Telangana">Telangana</option>
                                <option value="Tripura">Tripura</option>
                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                <option value="Uttarakhand">Uttarakhand</option>
                                <option value="West Bengal">West Bengal</option>
                            </select>
                        </div>
                         <div class="col-12">
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="address_type" value="Home" checked>
                                    <label class="form-check-label">Home</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="address_type" value="Work">
                                    <label class="form-check-label">Work</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-warning w-100 fw-bold text-white py-2" style="background-color: #fb641b;" id="btnSubmitAddress">SAVE AND DELIVER HERE</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function updateCartQty(id, change) {
        const input = document.getElementById('qty_cart_' + id);
        if (!input) return;

        let currentQty = parseInt(input.value);
        let newQty = currentQty + change;

        if (newQty < 1) return;

        fetch('includes/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + id + '&quantity=' + newQty
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating quantity: ' + (data.message || 'Unknown error'));
                }
            });
    }
</script>

<script>
    var addressModal = new bootstrap.Modal(document.getElementById('addressModal'));

    function toggleNewAddress() {
        resetForm();
        addressModal.show();
    }

    function resetForm() {
        document.getElementById("checkoutAddressForm").reset();
        document.getElementById("formAction").value = "new_address";
        document.getElementById("formTitle").innerHTML = '<i class="bi bi-plus-circle me-2"></i> Add a new address';
        document.getElementById("address_id").value = "";
        document.getElementById("btnSubmitAddress").innerText = "SAVE AND DELIVER HERE";
    }

    function editAddress(addr) {
        // Show modal
        addressModal.show();
        
        var form = document.getElementById("checkoutAddressForm");
        document.getElementById("formAction").value = "edit_address";
        document.getElementById("formTitle").innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Address';
        document.getElementById("address_id").value = addr.id;
        document.getElementById("btnSubmitAddress").innerText = "SAVE CHANGES";
        
        form.elements['name'].value = addr.name;
        form.elements['phone'].value = addr.phone;
        form.elements['pincode'].value = addr.pincode;
        form.elements['address'].value = addr.address;
        form.elements['city'].value = addr.city;
        form.elements['state'].value = addr.state;
        
        // Select radio manually
        var radios = form.elements['address_type'];
        for (var i=0; i<radios.length; i++) {
            if (radios[i].value === addr.address_type) {
                radios[i].checked = true;
            }
        }
    }
</script>