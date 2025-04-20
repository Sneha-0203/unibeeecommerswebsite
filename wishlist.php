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
    background-color: var(--primary-color);
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
    background-color: #2a75e6;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(58, 134, 255, 0.3);
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
        }  gap: 8px;
        
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
        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> cart</a></li>
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

    <     <!-- Footer Section -->
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
                    

const wishlistIcon = document.querySelector('.favorite-icon');
const wishlistBadge = wishlistIcon ? wishlistIcon.nextElementSibling : null;
                    
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
                    // Update this line
const cartIcon = document.querySelector('.fa-shopping-cart');
const cartBadge = cartIcon ? cartIcon.nextElementSibling : null;
                    
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