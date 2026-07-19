<?php
include 'includes/auth_check.php';
require_once 'config/database.php';
include 'includes/header.php';

// Fetch User Details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$user_id = $user['id']; // Ensure $user_id is available for data fetching

// Split name for First/Last Name display (simple logic)
$name_parts = explode(' ', $user['name'], 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

// Fetch Saved Addresses
$stmt_addr = $pdo->prepare("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC");
$stmt_addr->execute([$user_id]);
$saved_addresses = $stmt_addr->fetchAll();
?>

<style>
    /* White Background */
    body {
        background-color: #ffffff;
        min-height: 100vh;
    }

    /* Glassmorphism for Main Container */
    .glass-container {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
    }
</style>

<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>
<div class="min-vh-100 py-4 mt-5 pt-5">
    <div class="container">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-lg-3 mb-4">
                <!-- Hello User Card -->
                <div class="card shadow-sm mb-3 border-0 glass-container">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle d-flex justify-content-center align-items-center"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill text-primary fs-3"></i>
                        </div>
                        <div>
                            <small class="text-secondary fw-bold">Hello,</small>
                            <h6 class="mb-0 fw-bold">
                                <?= htmlspecialchars($user['name']) ?>
                            </h6>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <div class="card shadow-md border-0 glass-container">
                    <div class="list-group list-group-flush rounded-3 bg-transparent">

                        <div class="p-3 pb-0">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="bi bi-person-fill text-primary fs-5"></i>
                                <span class="fw-bold text-muted">ACCOUNT SETTINGS</span>
                            </div>
                        </div>

                        <a href="profile.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 fw-bold <?= $active_tab == 'profile' ? 'text-primary bg-primary-subtle' : 'text-muted bg-transparent' ?>">Profile
                            Information</a>

                        <a href="profile.php?tab=addresses"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 fw-bold <?= $active_tab == 'addresses' ? 'text-primary bg-primary-subtle' : 'text-muted bg-transparent' ?>">Manage
                            Addresses</a>

                        <a href="orders.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 fw-bold text-muted bg-transparent">My
                            Orders</a>

                        <a href="wishlist.php"
                            class="list-group-item list-group-item-action border-0 ps-5 py-2 fw-bold text-muted bg-transparent">My
                            Wishlist</a>



                        <div class="border-top text-center p-3">
                            <a href="logout.php" class="text-decoration-none fw-bold text-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-lg-9">
                <div class="card shadow-md border-0 h-100 glass-container" style="border-radius: 2px;">
                    <?php if ($active_tab == 'addresses'): ?>
                        <!-- MANAGE ADDRESSES VIEW -->
                        <div class="card-body p-0">
                            <div class="p-4 border-bottom">
                                <h5 class="fw-bold mb-0">Manage Addresses</h5>
                            </div>

                            <!-- Add New Button -->
                            <div class="p-3 border-bottom">
                                <button class="btn btn-light w-100 text-start text-primary fw-bold py-3 bg-white border"
                                    onclick="toggleAddressForm()">
                                    <i class="bi bi-plus-lg me-2"></i> ADD A NEW ADDRESS
                                </button>

                                <!-- Hidden Form -->
                                <div id="addressForm" class="mt-3 p-3 bg-light border rounded" style="display: none;">
                                    <h6 class="fw-bold text-primary mb-3" id="formTitle">ADD A NEW ADDRESS</h6>
                                    <form action="includes/add_address_profile.php" method="POST" id="addressFormElement">
                                        <input type="hidden" name="address_id" id="address_id" value="">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <input type="text" name="name" class="form-control" placeholder="Name"
                                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="phone" class="form-control"
                                                    placeholder="10-digit mobile number"
                                                    value="<?= htmlspecialchars($user['phone']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="pincode" class="form-control" placeholder="Pincode"
                                                    value="<?= isset($user['pincode']) ? htmlspecialchars($user['pincode']) : '' ?>"
                                                    required>
                                            </div>

                                            <div class="col-12">
                                                <textarea name="address" class="form-control" rows="3"
                                                    placeholder="Address (Area and Street)"
                                                    required><?= htmlspecialchars($user['address']) ?></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="city" class="form-control"
                                                    placeholder="City/District/Town"
                                                    value="<?= isset($user['city']) ? htmlspecialchars($user['city']) : '' ?>"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <select class="form-select" name="state" required>
                                                    <option value="" disabled selected>--Select State--</option>
                                                    <?php
                                                    $states = ["Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal", "Delhi", "Puducherry"];
                                                    foreach ($states as $state) {
                                                        $selected = (isset($user['state']) && $user['state'] == $state) ? 'selected' : '';
                                                        echo "<option value='$state' $selected>$state</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="landmark" class="form-control"
                                                    placeholder="Landmark (Optional)"
                                                    value="<?= isset($user['landmark']) ? htmlspecialchars($user['landmark']) : '' ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="alternate_phone" class="form-control"
                                                    placeholder="Alternate Mobile (Optional)"
                                                    value="<?= isset($user['alternate_phone']) ? htmlspecialchars($user['alternate_phone']) : '' ?>">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <div class="d-flex gap-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="address_type"
                                                            value="Home" checked>
                                                        <label class="form-check-label">Home</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="address_type"
                                                            value="Work">
                                                        <label class="form-check-label">Work</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <button type="submit" class="btn btn-primary fw-bold px-4">SAVE</button>
                                                <button type="button"
                                                    class="btn btn-link text-decoration-none text-primary fw-bold ms-2"
                                                    onclick="toggleAddressForm()">CANCEL</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Multiple Address List Item -->
                            <?php if (!empty($saved_addresses)): ?>
                                <?php foreach ($saved_addresses as $addr): ?>
                                    <div class="p-4 border-bottom position-relative hover-bg-light">
                                        <div class="dropdown position-absolute top-0 end-0 mt-3 me-3">
                                            <button class="btn btn-link text-secondary p-0" type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li><button class="dropdown-item small"
                                                        onclick='editAddress(<?= json_encode($addr) ?>)'>Edit</button></li>
                                                <li><a class="dropdown-item small text-danger"
                                                        href="includes/delete_address.php?id=<?= $addr['id'] ?>"
                                                        onclick="return confirm('Are you sure you want to delete this address?');">Delete</a>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-light text-secondary border rounded-1"
                                                style="font-size: 0.7rem;"><?= strtoupper($addr['address_type']) ?></span>
                                        </div>

                                        <div class="d-flex gap-3 mb-2">
                                            <span class="fw-bold"><?= htmlspecialchars($addr['name']) ?></span>
                                            <span class="fw-bold"><?= htmlspecialchars($addr['phone']) ?></span>
                                        </div>

                                        <p class="mb-0 text-muted small" style="max-width: 600px; line-height: 1.5;">
                                            <?= htmlspecialchars($addr['address']) ?>,
                                            <?= htmlspecialchars($addr['city']) ?>,
                                            <?= htmlspecialchars($addr['state']) ?> -
                                            <span class="fw-bold"><?= htmlspecialchars($addr['pincode']) ?></span>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="bi bi-geo-alt fs-1 mb-3 d-block"></i>
                                    <h6>No Saved Addresses</h6>
                                    <p class="small">Add a new address to manage your deliveries easily.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                        <script>
                            function toggleAddressForm() {
                                var x = document.getElementById("addressForm");
                                var form = document.getElementById("addressFormElement");
                                var title = document.getElementById("formTitle");

                                // Every time we toggle via "Add New", reset to "Add" mode
                                if (x.style.display === "none") {
                                    x.style.display = "block";
                                    resetForm();
                                } else {
                                    x.style.display = "none";
                                }
                            }

                            function resetForm() {
                                document.getElementById("addressFormElement").reset();
                                document.getElementById("addressFormElement").action = "includes/add_address_profile.php";
                                document.getElementById("formTitle").innerText = "ADD A NEW ADDRESS";
                                document.getElementById("address_id").value = "";
                            }

                            function editAddress(addr) {
                                var x = document.getElementById("addressForm");
                                var form = document.getElementById("addressFormElement");

                                // Show form
                                x.style.display = "block";

                                // Set Action to Edit
                                form.action = "includes/edit_address_profile.php";
                                document.getElementById("formTitle").innerText = "EDIT ADDRESS";
                                document.getElementById("address_id").value = addr.id;

                                // Populate fields
                                form.elements['name'].value = addr.name;
                                form.elements['phone'].value = addr.phone;
                                form.elements['pincode'].value = addr.pincode;
                                form.elements['address'].value = addr.address;
                                form.elements['city'].value = addr.city;
                                form.elements['state'].value = addr.state;
                                form.elements['landmark'].value = addr.landmark || '';
                                form.elements['alternate_phone'].value = addr.alternate_phone || '';

                                // Radio buttons
                                if (addr.address_type === 'Work') {
                                    form.elements['address_type'][1].checked = true;
                                } else {
                                    form.elements['address_type'][0].checked = true;
                                }

                                // Scroll to form
                                x.scrollIntoView({ behavior: 'smooth' });
                            }

                            // Auto-open if query param exists
                            document.addEventListener("DOMContentLoaded", function () {
                                const urlParams = new URLSearchParams(window.location.search);
                                if (urlParams.has('open_add')) {
                                    toggleAddressForm();
                                    // Optional: scroll to it
                                    document.getElementById("addressForm").scrollIntoView({ behavior: 'smooth' });
                                }
                            });
                        </script>

                    <?php else: ?>
                        <!-- USER PROFILE VIEW (Default) -->
                        <div class="card-body p-4">

                            <!-- Personal Information -->
                            <form action="includes/update_profile.php" method="POST" id="form-personal">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="fw-bold fs-5">Personal Information</h5>
                                    <button type="button"
                                        class="btn btn-link text-decoration-none fw-bold text-primary small p-0"
                                        id="btn-edit-personal" onclick="enableEdit('personal')">Edit</button>
                                    <div id="actions-personal" style="display: none;">
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">SAVE</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold ms-1"
                                            onclick="cancelEdit('personal')">CANCEL</button>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control field-personal" name="first_name"
                                            value="<?= htmlspecialchars($first_name) ?>" disabled required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control field-personal" name="last_name"
                                            value="<?= htmlspecialchars($last_name) ?>" disabled>
                                    </div>
                                </div>

                                <div class="mb-5">
                                    <label class="d-block text-muted small mb-2">Your Gender</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input field-personal" type="radio" name="gender"
                                                id="male" value="Male" checked disabled>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input field-personal" type="radio" name="gender"
                                                id="female" value="Female" disabled>
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Email Address -->
                            <form action="includes/update_profile.php" method="POST" id="form-email">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold fs-5">Email Address</h5>
                                    <button type="button"
                                        class="btn btn-link text-decoration-none fw-bold text-primary small p-0"
                                        id="btn-edit-email" onclick="enableEdit('email')">Edit</button>
                                    <div id="actions-email" style="display: none;">
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">SAVE</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold ms-1"
                                            onclick="cancelEdit('email')">CANCEL</button>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <input type="email" class="form-control w-50 field-email" name="email"
                                        value="<?= htmlspecialchars($user['email']) ?>" disabled required>
                                </div>
                            </form>

                            <!-- Mobile Number -->
                            <form action="includes/update_profile.php" method="POST" id="form-phone">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold fs-5">Mobile Number</h5>
                                    <button type="button"
                                        class="btn btn-link text-decoration-none fw-bold text-primary small p-0"
                                        id="btn-edit-phone" onclick="enableEdit('phone')">Edit</button>
                                    <div id="actions-phone" style="display: none;">
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">SAVE</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm fw-bold ms-1"
                                            onclick="cancelEdit('phone')">CANCEL</button>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <input type="text" class="form-control w-50 field-phone" name="phone"
                                        value="<?= htmlspecialchars($user['phone']) ?>" disabled required
                                        pattern="[0-9]{10}">
                                </div>
                            </form>



                        </div>

                        <script>
                            function enableEdit(section) {
                                // Hide Edit button, Show Actions
                                document.getElementById('btn-edit-' + section).style.display = 'none';
                                document.getElementById('actions-' + section).style.display = 'block';

                                // Enable inputs
                                const inputs = document.querySelectorAll('.field-' + section);
                                inputs.forEach(input => input.disabled = false);
                            }

                            function cancelEdit(section) {
                                // Show Edit button, Hide Actions
                                document.getElementById('btn-edit-' + section).style.display = 'block';
                                document.getElementById('actions-' + section).style.display = 'none';

                                // Disable inputs and Reset
                                const inputs = document.querySelectorAll('.field-' + section);
                                inputs.forEach(input => input.disabled = true);

                                // Reload page to reset values (simplest way to revert changes)
                                // Or we could store initial values in JS. For now, reload is acceptable or we can just let headers handle it.
                                // Actually, cancel usually just disables. If user typed something, it stays. 
                                // Better UX: location.reload() to fetch fresh DB data if they want to truly cancel edits.
                                location.reload();
                            }
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>