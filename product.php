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
// MODIFIED to use the existing wishlist table instead of wishlists and wishlist_items tables
$query = "SELECT p.*, c.name as category_name, 
          (SELECT SUM(stock) FROM product_sizes WHERE product_id = p.id) as total_stock,
          (SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = p.id) as in_wishlist
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

// Get wishlist count - MODIFIED to use existing wishlist table
$wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = ?";
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
        nav {
            background: linear-gradient(to right, #f12711, #f5af19);
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            width: 100%;
        }

        nav ul li {
            display: flex;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            transition: color 0.3s ease-in-out, background-color 0.3s ease;
            display: flex;
            align-items: center;
        }

        nav a:hover {
            color: #ff5722;
            background-color: rgba(255, 255, 255, 0.1);
        }

        nav a i {
            margin-right: 8px;
            font-size: 16px;
        }

        /* Brand styling */
        nav ul .brand {
            font-size: 22px;
            font-weight: bold;
            color: white;
            margin-right: auto;
            display: flex;
            align-items: center;
        }

        nav ul .brand i {
            margin-right: 8px;
        }

        /* Icon-specific styling */
        nav a .fa-heart {
            color: white;
            transition: color 0.3s ease;
        }

        nav a:hover .fa-heart {
            color: #ff5722;
        }

        nav a .fa-shopping-cart {
            color: white;
            transition: color 0.3s ease;
        }

        nav a:hover .fa-shopping-cart {
            color: #ff5722;
        }
        /* Updated Profile Dropdown Styles */
          /* Updated Profile Dropdown Styles */
          #cart-count {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: #ff5722;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Profile dropdown styling */
        .profile-dropdown {
            position: relative;
            cursor: pointer;
        }

        .profile-dropdown a.user-logged-in {
            display: flex;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.1);
            color: white;
            border-radius: 20px;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .profile-dropdown a.user-logged-in:hover {
            background-color: rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-dropdown a.user-logged-in i.fa-user-circle {
            font-size: 18px;
            margin-right: 6px;
        }

        .profile-dropdown i.fa-caret-down {
            margin-left: 6px;
            transition: transform 0.3s ease;
        }

        .profile-dropdown:hover i.fa-caret-down {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            border-radius: 8px;
            margin-top: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid #eee;
            display: none;
            z-index: 1100;
            transform-origin: top center;
            transform: scaleY(0);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
            transform: scaleY(1);
            opacity: 1;
        }

        .dropdown-content a {
            display: flex !important;
            align-items: center;
            padding: 12px 16px;
            color: #333;
            font-weight: 400 !important;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            color: #ff5722;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
            color: #ff5722;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            nav ul {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            nav ul .brand {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
        }
/* Updated Profile Dropdown Styles */
#cart-count {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    background-color: #ff5722;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    margin-left: 5px;
    margin-top: 3%;
    
}

/* Profile dropdown styling */
.profile-dropdown {
    position: relative;
    cursor: pointer;

}

.profile-dropdown a.user-logged-in {
    display: flex;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.1);
    color: white;
    border-radius: 20px;
    padding: 6px 12px;
    transition: all 0.3s ease;
}

.profile-dropdown a.user-logged-in:hover {
    background-color: rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.profile-dropdown a.user-logged-in i.fa-user-circle {
    font-size: 18px;
    margin-right: 6px;
}

.profile-dropdown i.fa-caret-down {
    margin-left: 6px;
    transition: transform 0.3s ease;
}

.profile-dropdown:hover i.fa-caret-down {
    transform: rotate(180deg);
}

.dropdown-content {
    position: absolute;
    right: 0;
    background-color: white;
    min-width: 200px;
    border-radius: 8px;
    margin-top:90%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    border: 1px solid #eee;
    display: none;
    z-index: 1100;
    transform-origin: top center;
    transform: scaleY(0);
    opacity: 0;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.profile-dropdown:hover .dropdown-content {
    display: block;
    transform: scaleY(1);
    opacity: 1;
}

.dropdown-content a {
    display: flex !important;
    align-items: center;
    padding: 12px 16px;
    color: #333;
    font-weight: 400 !important;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-content a:last-child {
    border-bottom: none;
}

.dropdown-content a:hover {
            color: #ff5722;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
            color: #ff5722;
        }

/* Responsive adjustments */
@media (max-width: 768px) {
    nav ul {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    nav ul .brand {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
}

/* Banner Styles */
.banner {
    background: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5)), url('images/banner.jpg');
    background-size: cover;
    background-position: center;
    height: 500px;
    display: flex;
    align-items: center;
    color: white;
    text-align: center;
}

.banner-content {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}

.banner-content h2 {
    font-size: 42px;
    margin-bottom: 20px;
    font-weight: 700;
}

.banner-content p {
    font-size: 18px;
    margin-bottom: 30px;
}

.btn {
    display: inline-block;
    background-color:  #FF5500;
    color: white;
    padding: 10px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn:hover {
    background-color: rgb(94, 92, 92);
    transform: translateY(-3px);

}

/* Enhanced Featured Products Section */
.section-title {
    text-align: center;
    margin: 50px 0 30px;
    font-size: 32px;
    font-weight: 700;
    color: var(--dark-color);
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: var(--primary-color);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

/* Enhanced Product Card */
.product-card {
    background-color: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.4s ease;
    position: relative;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.product-image {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.product-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.1);
    opacity: 0;
    transition: all 0.3s ease;
}

.product-card:hover .product-image::before {
    opacity: 1;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.product-tag {
    position: absolute;
    top: 15px;
    left: 15px;
    background-color: var(--secondary-color);
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.product-info {
    padding: 20px;
}

.product-info h3 {
    margin-bottom: 10px;
    font-size: 18px;
    color: var(--dark-color);
    font-weight: 600;
    transition: all 0.3s ease;
}

.product-card:hover .product-info h3 {
    color: var(--primary-color);
}

.product-description {
    color: #777;
    font-size: 14px;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.price {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 22px;
    margin-bottom: 20px;
    display: block;
}

.product-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-actions .btn {
    padding: 8px 16px;
    font-size: 14px;
}

.add-to-cart {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f0f0f0;
    color: var(--dark-color);
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-to-cart:hover {
    background-color: var(--primary-color);
    color: white;
    transform: rotate(360deg);
}
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
        footer {
    background: #000; /* Black background */
    color: #fff; /* White text */
    padding: 20px 0;
    font-size: 18px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.footer-col h3 {
    margin-bottom: 15px;
    font-size: 22px;
    position: relative;
    padding-bottom: 8px;
    color: #fff; /* White heading */
    text-decoration: underline; /* Underline effect */
    text-decoration-thickness: 3px; /* Thicker underline */
    text-underline-offset: 5px; /* Space between text and underline */
    text-decoration-color: #ff9800; /* Orange underline */
    transition: color 0.3s ease, text-decoration-color 0.3s ease;
}

/* Hover Effect for Headings */
.footer-col h3:hover {
    color: #ff9800; /* Change text color to orange */
    text-decoration-color: #fff; /* Change underline to white */
}

.footer-col ul {
    list-style: none;
    padding: 0;
}

.footer-col ul li {
    margin-bottom: 10px;
}

/* Links */
.footer-col ul li a {
    font-size: 18px;
    color: #ccc; /* Light gray for contrast */
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
}

/* Hover Effect for Links */
.footer-col ul li a:hover {
    color: #ff9800; /* Bright orange on hover */
    padding-left: 5px;
}

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .social-links a i {
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            transform: scale(1.2) translateY(-5px);
        }

        .social-links a:nth-child(1) i { color: #1877F2; }
        .social-links a:nth-child(2) i { color: #1DA1F2; }
        .social-links a:nth-child(3) i { color: #C13584; }
        .social-links a:nth-child(4) i { color: #E60023; }
        .social-links a:nth-child(5) i { color: #25D366; }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

    </style>
</head>
<body>
 <!-- Navigation -->
 <nav>
        
            
        <ul>

        <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
        <li><a href="wishlist.php"><i class="fas fa-shopping-cart"></i> cart</a></li>
        <a href="user-order_history.php"><i class="fas fa-shopping-bag"></i> My Orders</a>                               

        <li class="profile-dropdown">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="#" id="profile-btn" class="user-logged-in">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo $_SESSION['user_name']; ?> 
                        <i class="fas fa-caret-down"></i>
                    </a>
                <?php else: ?>
                    <a href="#" id="profile-btn">
                        <i class="fas fa-user"></i> Profile 
                        <i class="fas fa-caret-down"></i>
                    </a>
                <?php endif; ?>
                <div class="dropdown-content">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="#" id="login-dropdown-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="#" id="signup-dropdown-btn"><i class="fas fa-user-plus"></i> Sign Up</a>
                        <a href="admin_login.php"><i class="fas fa-lock"></i> Admin Login</a>
                    <?php else: ?>
                        <a href="profile.php"><i class="fas fa-id-card"></i> My Profile</a>
                         <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2>Our Shoes Collection</h2>
                <p class="text-muted">Find the perfect pair for every occasion</p>
            </div>
         
    <!-- Add this code to your product.php page somewhere visible, perhaps above the product grid -->
<div class="category-links-container my-4">
    <h4>Shop by Category</h4>
    <div class="d-flex flex-wrap gap-2 mt-2">
        <a href="product.php" class="btn <?php echo $categoryFilter == 0 ? 'btn-primary' : 'btn-outline-primary'; ?>">
            All Products
        </a>
        <?php 
        // Reset the categories result set pointer
        $categoriesResult->data_seek(0);
        while ($category = $categoriesResult->fetch_assoc()): 
        ?>
            <a href="product.php?category=<?php echo $category['id']; ?>" 
               class="btn <?php echo $categoryFilter == $category['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
        <?php endwhile; ?>
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
                                <p class="card-text text-success fw-bold price-tag">₹<?php echo number_format($product['base_price'], 2); ?></p>
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
          <!-- Footer Section -->
          <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>UNIBEE</h3>
                    <p>Your ultimate destination for stylish and comfortable footwear.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-home" style="color: #4CAF50;"></i> Home</a></li>
                        <li><a href="product.php"><i class="fas fa-shopping-bag" style="color: #FF9800;"></i> Products</a></li>
                        <li><a href="aboutus.php"><i class="fas fa-info-circle" style="color: #2196F3;"></i> About Us</a></li>
                        <li><a href="contact.html"><i class="fas fa-envelope" style="color: #E91E63;"></i> Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="faq.php"><i class="fas fa-question-circle" style="color: #FF5722;"></i> FAQ</a></li>
                        <li><a href="shipping-policy.php"><i class="fas fa-shipping-fast" style="color: #FF9800;"></i> Shipping Policy</a></li>
                        <li><a href="return-policy.php"><i class="fas fa-undo" style="color: #E91E63;"></i> Returns & Exchanges</a></li>
                        <li><a href="terms.php"><i class="fas fa-file-contract" style="color: #9C27B0;"></i> Terms & Conditions</a></li>
                    
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><a href="https://maps.google.com?q=123 Shoe Street, Fashion City" target="_blank"><i class="fas fa-map-marker-alt" style="color: #FFC107;"></i> 123 Shoe Street, Fashion City</a></li>
                        <li><a href="tel:+12345678900"><i class="fas fa-phone" style="color: #4CAF50;"></i> +1 234 567 8900</a></li>
                        <li><a href="mailto:info@unibee.com"><i class="fas fa-envelope" style="color: #2196F3;"></i> info@unibee.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 UNIBEE. All Rights Reserved.</p>
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
            // Try to find the heart icon in the navigation
            const navHeartIcon = document.querySelector('nav a .fa-heart');
            
            if (navHeartIcon) {
                // Add pulse animation to the heart icon
                navHeartIcon.classList.add('pulse');
                setTimeout(() => {
                    navHeartIcon.classList.remove('pulse');
                }, 500);
                
                // Find the parent link element to add the badge to
                const navIconBtn = navHeartIcon.closest('a');
                
                // Find existing badge or create new one
                let wishlistBadge = document.getElementById('wishlist-count');
                if (!wishlistBadge && data.wishlist_count > 0) {
                    wishlistBadge = document.createElement('span');
                    wishlistBadge.className = 'nav-icon-badge';
                    wishlistBadge.id = 'wishlist-count';
                    navIconBtn.appendChild(wishlistBadge);
                }
                
                if (wishlistBadge) {
                    if (data.wishlist_count > 0) {
                        wishlistBadge.textContent = data.wishlist_count;
                        wishlistBadge.style.display = 'inline-flex';
                    } else {
                        wishlistBadge.style.display = 'none';
                    }
                }
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