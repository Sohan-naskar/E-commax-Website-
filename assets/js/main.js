// Force Scroll to Top on Refresh
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
    console.log('Scroll restoration set to manual');
}
window.onbeforeunload = function () {
    window.scrollTo(0, 0);
}

// Theme Toggle Logic
document.addEventListener('DOMContentLoaded', function () {
    window.scrollTo(0, 0);
    console.log('Main.js loaded');

    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const body = document.body;
    const currentTheme = localStorage.getItem('theme');

    if (currentTheme) {
        console.log('Restoring theme:', currentTheme);
        body.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
            if (themeIcon) {
                themeIcon.classList.remove('bi-moon');
                themeIcon.classList.add('bi-sun');
            }
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            console.log('Theme toggle clicked');
            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                if (themeIcon) {
                    themeIcon.classList.remove('bi-sun');
                    themeIcon.classList.add('bi-moon');
                }
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                if (themeIcon) {
                    themeIcon.classList.remove('bi-moon');
                    themeIcon.classList.add('bi-sun');
                }
            }
        });
    } else {
        console.error('Theme toggle button not found');
    }
});

// Toast Notification Helper
function showToast(message, type = 'primary') {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toast-message');

    if (toastEl && toastBody) {
        // Reset classes
        // type: primary, success, danger, warning, info, dark, light
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;

        // Set message
        toastBody.textContent = message;

        // Initialize and show (using Bootstrap 5 API)
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    } else {
        // Fallback
        console.log('Toast element not found');
        alert(message);
    }
}

// Add to Cart
function addToCart(productId) {
    fetch(`includes/cart_action.php?action=add_ajax&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update Badge
                const badges = document.querySelectorAll('.badge-float');
                badges.forEach(badge => badge.textContent = data.cart_count);

                showToast(data.message, 'success');
            } else {
                showToast('Failed to add to cart', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error adding to cart', 'danger');
        });
}

// Buy Now (Add to Cart + Redirect)
function buyNow(productId) {
    fetch(`includes/cart_action.php?action=add_ajax&id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'cart.php';
            } else {
                showToast('Failed to process request', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error processing request', 'danger');
        });
}

// Add/Toggle Wishlist
function addToWishlist(productId, btnElement) {
    const formData = new FormData();
    formData.append('action', 'add'); // server handles adding logic, assumes toggle intent on failure or check? Wait, I saw logic before.
    // Logic was: if exists -> remove. if not -> add.
    // So sending 'add' triggers the toggle check if configured that way.
    // Let's verify standard usage. 
    // Yes, my previous edit to wishlist_action.php handled 'add' as 'toggle' or 'add || toggle'.

    formData.append('product_id', productId);

    let originalHtml = '';

    if (btnElement) {
        originalHtml = btnElement.innerHTML;
        // Spinner logic
        if (btnElement.classList.contains('rounded-circle')) {
            btnElement.innerHTML = '<span class="loading-spinner" style="width: 0.8rem; height: 0.8rem; border-color: #000; border-top-color: transparent;"></span>';
        } else {
            btnElement.innerHTML = '<span class="loading-spinner" style="width: 0.8rem; height: 0.8rem;"></span>';
        }
        btnElement.disabled = true;
    }

    fetch('includes/wishlist_action.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (btnElement) {
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }

            if (data.success) {
                if (btnElement) {
                    const icon = btnElement.querySelector('i');
                    if (icon) {
                        if (data.action === 'added') {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill', 'text-danger');
                            showToast('Added to Wishlist', 'success');
                        } else if (data.action === 'removed') {
                            icon.classList.remove('bi-heart-fill', 'text-danger');
                            icon.classList.add('bi-heart');
                            showToast('Removed from the wishlist', 'danger');
                        }
                    }
                }
            } else {
                showToast(data.message, 'warning');
            }
        })
        .catch(err => {
            if (btnElement) {
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }
            console.error('Error adding/removing wishlist:', err);
            showToast('Error communicating with server', 'danger');
        });
}

// Update Cart Quantity
function updateQuantity(productId, action) {
    const input = document.getElementById(`qty-${productId}`);
    let newQty = parseInt(input.value);

    if (action === 'increase') {
        newQty++;
    } else if (action === 'decrease' && newQty > 1) {
        newQty--;
    } else {
        return;
    }

    // Optimistic UI update
    input.value = newQty;

    const formData = new FormData();
    formData.append('id', productId);
    formData.append('quantity', newQty);

    fetch('includes/update_cart.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to update totals
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(err => console.error(err));
}

// Remove from Cart
function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item?')) {
        window.location.href = `includes/cart_action.php?action=remove&id=${productId}`;
    }
}
// Quick View Logic
function openQuickView(productId) {
    const modalEl = document.getElementById('quickViewModal');
    const modal = new bootstrap.Modal(modalEl);

    // Reset content
    document.getElementById('qv-title').textContent = 'Loading...';
    document.getElementById('qv-price').textContent = '';
    document.getElementById('qv-description').textContent = '';
    document.getElementById('qv-image').src = 'assets/images/placeholder.png'; // standard placeholder or loading spinner

    modal.show();

    fetch(`includes/get_product_details.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const p = data.product;
                document.getElementById('qv-title').textContent = p.name;
                document.getElementById('qv-category').textContent = p.category || 'Product';
                document.getElementById('qv-price').textContent = '₹' + parseFloat(p.price).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                document.getElementById('qv-description').textContent = p.description;
                document.getElementById('qv-image').src = p.image;

                // Update buttons
                document.getElementById('qv-add-to-cart').onclick = function () { addToCart(p.id); modal.hide(); };
                document.getElementById('qv-view-details').href = `product.php?id=${p.id}`;
            } else {
                showToast(data.message, 'danger');
                modal.hide();
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Failed to load product details', 'danger');
            modal.hide();
        });
}
