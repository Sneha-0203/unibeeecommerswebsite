<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection
require_once 'config/database.php';

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get categories for filter - FIXED: Use DISTINCT to prevent duplicates
$categoriesQuery = "SELECT DISTINCT id, name FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);

// Handle category filter with proper sanitization
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$whereClause = $categoryFilter > 0 ? "WHERE p.category_id = ?" : "";
// Prepare the products query with parameters to prevent SQL injection
// Updated to use wishlists and wishlist_items tables
$query = "SELECT p.*, c.name as category_name, 
          (SELECT SUM(stock) FROM product_sizes WHERE product_id = p.id) as total_stock,
          (SELECT COUNT(*) FROM wishlist_items wi 
           INNER JOIN wishlists w ON wi.wishlist_id = w.id 
           WHERE w.user_id = ? AND wi.product_id = p.id) as in_wishlist
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          $whereClause
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if ($categoryFilter > 0) {
    $stmt->bind_param("ii", $userId, $categoryFilter);
} else {
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$result = $stmt->get_result();

// Get cart count
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;

// Get wishlist count - Updated to join wishlists and wishlist_items tables
$wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist_items wi 
                  INNER JOIN wishlists w ON wi.wishlist_id = w.id 
                  WHERE w.user_id = ?";
$wishlistStmt = $conn->prepare($wishlistQuery);
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
    <title>Products - UNIBEE</title>
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
        /* Improved dropdown styling */
        .filter-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
            background-color: #fff;
        }
        .filter-btn {
            border-radius: 6px;
            padding: 8px 16px;
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
        /* Wishlist icon styling */
        .wishlist-icon {
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
        }
        .wishlist-icon:hover {
            background-color: rgba(255, 255, 255, 1);
            transform: scale(1.1);
        }
        .wishlist-icon.active {
            color: #dc3545;
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
        /* Favorite icon animation */
        .favorite-icon.pulse {
            animation: pulse 0.5s;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        /* Add to wishlist button */
        .add-to-wishlist {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #ced4da;
            padding: 5px 10px;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .add-to-wishlist:hover {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        .add-to-wishlist.active {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
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
                        <a class="nav-link active" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orderHistory.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="navbar-icons me-3">
                        <!-- Heart/Favorites Icon -->
                        <a href="wishlist.php" class="nav-icon-btn text-white me-2" title="Favorites">
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
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2>Our Shoes Collection</h2>
                <p class="text-muted">Find the perfect pair for every occasion</p>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex">
                    <select name="category" class="form-select filter-select me-2">
                        <option value="0">All Categories</option>
                        <?php while ($category = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($categoryFilter == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary filter-btn">Filter</button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card product-card">
                            <!-- Wishlist icon -->
                            <div class="wishlist-icon <?php echo ($product['in_wishlist'] > 0) ? 'active' : ''; ?>" 
                                 onclick="toggleWishlist(this, <?php echo $product['id']; ?>)">
                                <i class="<?php echo ($product['in_wishlist'] > 0) ? 'fas' : 'far'; ?> fa-heart"></i>
                            </div>
                            
                            <?php if ($product['total_stock'] === null || $product['total_stock'] <= 0): ?>
                                <span class="badge bg-danger badge-stock">Out of Stock</span>
                            <?php elseif ($product['total_stock'] < 10): ?>
                                <span class="badge bg-warning text-dark badge-stock">Low Stock</span>
                            <?php endif; ?>
                            
                            <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <span class="badge bg-secondary mb-2 category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <p class="card-text text-success fw-bold price-tag">$<?php echo number_format($product['base_price'], 2); ?></p>
                                <div class="card-actions">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary details-btn">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No products found. Please check back later or try another category.
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
        // Function to toggle wishlist items
        function toggleWishlist(element, productId) {
            // Toggle active class
            element.classList.toggle('active');
            
            // Toggle heart icon
            const heartIcon = element.querySelector('i');
            heartIcon.classList.toggle('far');
            heartIcon.classList.toggle('fas');
            
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
                    const navHeartIcon = document.querySelector('.favorite-icon');
                    
                    // Add pulse animation to the heart icon
                    navHeartIcon.classList.add('pulse');
                    setTimeout(() => {
                        navHeartIcon.classList.remove('pulse');
                    }, 500);
                    
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
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again later.', 'danger');
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