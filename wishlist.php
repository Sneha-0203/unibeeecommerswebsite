<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
require_once 'config/database.php';

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if ($stmt === false) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get wishlist items
// Join products, product_sizes, categories, wishlists and wishlist_items tables
$query = "SELECT p.*, c.name as category_name, 
          (SELECT SUM(stock) FROM product_sizes WHERE product_id = p.id) as total_stock,
          w.id as wishlist_id, wi.id as wishlist_item_id
          FROM wishlist_items wi
          INNER JOIN wishlists w ON wi.wishlist_id = w.id
          INNER JOIN products p ON wi.product_id = p.id
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE w.user_id = ?
          ORDER BY wi.added_at DESC";  // Changed from created_at to added_at


$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Database error in wishlist query: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Get cart count
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
if ($cartStmt === false) {
    die("Database error in cart query: " . $conn->error);
}
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;

// Get wishlist count
$wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist_items wi 
                  INNER JOIN wishlists w ON wi.wishlist_id = w.id 
                  WHERE w.user_id = ?";
$wishlistStmt = $conn->prepare($wishlistQuery);
if ($wishlistStmt === false) {
    die("Database error in wishlist count query: " . $conn->error);
}
$wishlistStmt->bind_param("i", $userId);
$wishlistStmt->execute();
$wishlistResult = $wishlistStmt->get_result();
$wishlistData = $wishlistResult->fetch_assoc();
$wishlistCount = $wishlistData['wishlist_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - UNIBEE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.3s;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .badge-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            border-radius: 20px;
            padding: 6px 12px;
            font-weight: 500;
        }
        /* Category badge styling */
        .category-badge {
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        /* Footer link hover effect */
        footer a.text-white:hover {
            color: #f8f9fa !important;
            text-decoration: underline;
        }
        /* Price tag styling */
        .price-tag {
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }
        /* Improved button styling */
        .details-btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        /* Remove button styling */
        .remove-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 10;
            color: #dc3545;
        }
        .remove-btn:hover {
            background-color: #dc3545;
            color: white;
            transform: scale(1.1);
        }
        /* Card action buttons */
        .card-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .card-actions .btn {
            flex: 1;
        }
        .card-actions .btn:first-child {
            margin-right: 5px;
        }
        /* Toast notification */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }
        /* Enhanced navbar icons */
        .nav-icon-btn {
            position: relative;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .nav-icon-btn i {
            font-size: 1.2rem;
        }
        .nav-icon-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .nav-icon-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.65rem;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-weight: bold;
        }
        .navbar-icons {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Empty wishlist styling */
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-wishlist i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .empty-wishlist h3 {
            margin-bottom: 15px;
            color: #6c757d;
        }
        /* Add to cart button animation */
        .add-to-cart-btn {
            position: relative;
            overflow: hidden;
        }
        .add-to-cart-btn::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        .add-to-cart-btn.clicked::after {
            animation: ripple 1s ease-out;
        }
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        /* Add hover effect to the product images */
        .image-container {
            position: relative;
            overflow: hidden;
        }
        .image-container::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.05);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .image-container:hover::after {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">UNIBEE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orderHistory.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="navbar-icons me-3">
                        <!-- Heart/Favorites Icon -->
                        <a href="wishlist.php" class="nav-icon-btn text-white me-2 active" title="Favorites">
                            <i class="fas fa-heart favorite-icon"></i>
                            <?php if ($wishlistCount > 0): ?>
                            <span class="nav-icon-badge"><?php echo htmlspecialchars($wishlistCount); ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Cart Icon -->
                        <a href="cart.php" class="nav-icon-btn text-white" title="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cartCount > 0): ?>
                            <span class="nav-icon-badge"><?php echo htmlspecialchars($cartCount); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="wishlist.php">My Favorites</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-heart text-danger me-2"></i> My Favorites</h2>
                <p class="text-muted">Items you've saved for later</p>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 product-item" data-product-id="<?php echo $product['id']; ?>">
                        <div class="card product-card">
                            <!-- Remove from wishlist button -->
                            <div class="remove-btn" onclick="removeFromWishlist(this, <?php echo $product['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </div>
                            
                            <?php if ($product['total_stock'] === null || $product['total_stock'] <= 0): ?>
                                <span class="badge bg-danger badge-stock">Out of Stock</span>
                            <?php elseif ($product['total_stock'] < 10): ?>
                                <span class="badge bg-warning text-dark badge-stock">Low Stock</span>
                            <?php endif; ?>
                            
                            <div class="image-container">
                                <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                                     class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <span class="badge bg-secondary mb-2 category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <p class="card-text text-success fw-bold price-tag">$<?php echo number_format($product['base_price'], 2); ?></p>
                                <div class="card-actions">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                    <?php if ($product['total_stock'] > 0): ?>
                                        <button class="btn btn-primary add-to-cart-btn" onclick="quickAddToCart(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-wishlist">
                        <i class="far fa-heart"></i>
                        <h3>Your favorites list is empty</h3>
                        <p class="text-muted mb-4">Save items you love to your favorites list so you can find them easily later.</p>
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shoe-prints me-2"></i> Explore Products
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast notification container -->
    <div class="toast-container"></div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>UNIBEE</h5>
                    <p>Quality footwear for every occasion.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Shoe Street, Fashion City</p>
                        <p><i class="fas fa-phone me-2"></i> (123) 456-7890</p>
                        <p><i class="fas fa-envelope me-2"></i> info@unibee.com</p>
                    </address>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> UNIBEE. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to remove from wishlist
        function removeFromWishlist(element, productId) {
            // Get the parent product item
            const productItem = element.closest('.product-item');
            
            // Send AJAX request to update wishlist
            fetch('api/toggle_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                // Show toast notification
                showToast(data.message, data.success ? 'success' : 'danger');
                
                // Update wishlist count in nav
                if (data.wishlist_count !== undefined) {
                    const wishlistBadge = document.querySelector('.favorite-icon').nextElementSibling;
                    
                    if (data.wishlist_count > 0) {
                        if (wishlistBadge) {
                            wishlistBadge.textContent = data.wishlist_count;
                        } else {
                            const navIconBtn = document.querySelector('.favorite-icon').parentElement;
                            const badge = document.createElement('span');
                            badge.className = 'nav-icon-badge';
                            badge.textContent = data.wishlist_count;
                            navIconBtn.appendChild(badge);
                        }
                    } else if (wishlistBadge) {
                        wishlistBadge.remove();
                    }
                }
                
                // Remove the product item from the page with animation
                productItem.style.opacity = '0';
                setTimeout(() => {
                    productItem.remove();
                    
                    // Check if there are no more items
                    const remainingItems = document.querySelectorAll('.product-item');
                    if (remainingItems.length === 0) {
                        const row = document.querySelector('.row.g-4');
                        row.innerHTML = `
                            <div class="col-12">
                                <div class="empty-wishlist">
                                    <i class="far fa-heart"></i>
                                    <h3>Your favorites list is empty</h3>
                                    <p class="text-muted mb-4">Save items you love to your favorites list so you can find them easily later.</p>
                                    <a href="products.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shoe-prints me-2"></i> Explore Products
                                    </a>
                                </div>
                            </div>
                        `;
                    }
                }, 300);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again later.', 'danger');
            });
        }
        
        // Function to quickly add an item to cart
        function quickAddToCart(productId) {
            const button = event.currentTarget;
            
            // Add clicked class for button animation
            button.classList.add('clicked');
            setTimeout(() => {
                button.classList.remove('clicked');
            }, 1000);
            
            // Update button temporarily
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;
            
            // Send AJAX request to add to cart
            fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1,
                    size_id: null  // Default size or null to be handled by the back-end
                })
            })
            .then(response => response.json())
            .then(data => {
                // Show toast notification
                showToast(data.message, data.success ? 'success' : 'danger');
                
                // Update cart count in nav
                if (data.cart_count !== undefined) {
                    const cartBadge = document.querySelector('.fa-shopping-cart').nextElementSibling;
                    
                    if (data.cart_count > 0) {
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        } else {
                            const navIconBtn = document.querySelector('.fa-shopping-cart').parentElement;
                            const badge = document.createElement('span');
                            badge.className = 'nav-icon-badge';
                            badge.textContent = data.cart_count;
                            navIconBtn.appendChild(badge);
                        }
                    } else if (cartBadge) {
                        cartBadge.remove();
                    }
                }
                
                // Reset button
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }, 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again later.', 'danger');
                
                // Reset button
                button.innerHTML = originalContent;
                button.disabled = false;
            });
        }
        
        // Function to show toast notification
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            
            bsToast.show();
            
            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
    </script>
</body>
</html>