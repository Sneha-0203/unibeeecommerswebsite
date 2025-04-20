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

// Get product ID and validate
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}
$productId = (int)$_GET['id'];

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Invalid user session
    session_destroy();
    header('Location: login.php');
    exit();
}

$user = $result->fetch_assoc();

// Get product details with category information
$productQuery = "SELECT p.*, c.name as category_name, c.description as category_description 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?";
$productStmt = $conn->prepare($productQuery);
$productStmt->bind_param("i", $productId);
$productStmt->execute();
$productResult = $productStmt->get_result();

if ($productResult->num_rows === 0) {
    header('Location: products.php');
    exit();
}

$product = $productResult->fetch_assoc();

// Get product sizes with stock information
$sizesQuery = "SELECT * FROM product_sizes WHERE product_id = ? ORDER BY size";
$sizesStmt = $conn->prepare($sizesQuery);
$sizesStmt->bind_param("i", $productId);
$sizesStmt->execute();
$sizesResult = $sizesStmt->get_result();

// Check if product has any sizes in stock
$hasSizesInStock = false;
$sizes = [];
while ($size = $sizesResult->fetch_assoc()) {
    $sizes[] = $size;
    if ($size['stock'] > 0) {
        $hasSizesInStock = true;
    }
}

// Get cart count
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;

// Handle add to cart request
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Validate and sanitize inputs
    $sizeId = filter_input(INPUT_POST, 'size_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
    
    if ($sizeId && $quantity) {
        // Verify if size exists and has stock
        $checkSizeQuery = "SELECT * FROM product_sizes WHERE id = ? AND product_id = ? AND stock >= ?";
        $checkSizeStmt = $conn->prepare($checkSizeQuery);
        $checkSizeStmt->bind_param("iii", $sizeId, $productId, $quantity);
        $checkSizeStmt->execute();
        $checkSizeResult = $checkSizeStmt->get_result();
        
        if ($checkSizeResult->num_rows > 0) {
            $sizeData = $checkSizeResult->fetch_assoc();
            
            // Check if product is already in cart
            $checkCartQuery = "SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND product_size_id = ?";
            $checkCartStmt = $conn->prepare($checkCartQuery);
            $checkCartStmt->bind_param("iii", $userId, $productId, $sizeId);
            $checkCartStmt->execute();
            $checkCartResult = $checkCartStmt->get_result();
            
            if ($checkCartResult->num_rows > 0) {
                // Update existing cart item
                $cartItem = $checkCartResult->fetch_assoc();
                $newQuantity = $cartItem['quantity'] + $quantity;
                
                // Check if new quantity exceeds available stock
                if ($newQuantity > $sizeData['stock']) {
                    $message = "Cannot add more items. Requested quantity exceeds available stock.";
                    $messageType = "warning";
                } else {
                    $updateCartQuery = "UPDATE cart SET quantity = ?, created_at = NOW() WHERE id = ?";
                    $updateCartStmt = $conn->prepare($updateCartQuery);
                    $updateCartStmt->bind_param("ii", $newQuantity, $cartItem['id']);
                    
                    if ($updateCartStmt->execute()) {
                        $message = "Cart updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Failed to update cart. Please try again.";
                        $messageType = "danger";
                    }
                }
            } else {
                // Add new cart item
                $addCartQuery = "INSERT INTO cart (user_id, product_id, product_size_id, quantity) VALUES (?, ?, ?, ?)";
                $addCartStmt = $conn->prepare($addCartQuery);
                $addCartStmt->bind_param("iiii", $userId, $productId, $sizeId, $quantity);
                
                if ($addCartStmt->execute()) {
                    $message = "Product added to cart successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to add product to cart. Please try again.";
                    $messageType = "danger";
                }
            }
        } else {
            $message = "Selected size is not available in the requested quantity.";
            $messageType = "danger";
        }
    } else {
        $message = "Invalid input. Please select a valid size and quantity.";
        $messageType = "danger";
    }
    
    // Refresh cart count
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    $cartData = $cartResult->fetch_assoc();
    $cartCount = $cartData['cart_count'] ?: 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - </title>
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
    background-color: #ff5722;
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
/* Clean white background card for all product images */
.product-card-white {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Consistent styling for all shoe images */
.shoe-product-image {
    transition: all 0.4s ease;
}

/* Subtle hover effect that works with white background */
.shoe-product-image:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 8px 15px rgba(0, 0, 150, 0.15));
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}
.breadcrumb-wrapper {
    display: flex;
    align-items: center;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    overflow-x: auto; /* handle overflow gracefully */
}

.breadcrumb {
    background-color: #f8f9fa;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 0;
    white-space: nowrap; /* keeps items on one line */
    display: flex; /* make sure items stay in row */
    align-items: center;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #999;
    padding: 0 8px;
    font-size: 18px;
}

.breadcrumb a {
    color: #FF5500;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: #e84300;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #555;
    font-weight: bold;
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
.btn-red {
    background-color:  #FF5500;
    color: white;
    border: none;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-red:hover {
    background-color:rgb(94, 92, 92);
    color: white;
}

.btn-continue {
    background-color: #FF5500;
    color: white;
    border: 1px solid #ccc;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-continue:hover {
    background-color:rgb(94, 92, 92);
    color: white;
}

footer {
    background: #000; /* Black background */
    color: #fff; /* White text */
    padding: 20px 0;
    font-size: 18px;
}
        .product-image {
            height: 500px;
            object-fit: contain;
            width: 100%;
        }
        .size-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .size-radio {
            display: none;
        }
        .size-label {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            text-align: center;
            min-width: 60px;
        }
        .size-radio:checked + .size-label {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .size-radio:disabled + .size-label {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            max-width: 150px;
        }
        .quantity-selector button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
        }
        /* Product image transformations and blending effects */
.product-image {
    transition: all 0.3s ease-in-out;
}

/* Zoom effect on hover */
.product-image:hover {
    transform: scale(1.05);
}

/* Shadow effect on hover */
.product-shadow:hover {
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Rotate effect on hover */
.product-rotate:hover {
    transform: rotate(2deg) scale(1.03);
}

/* Brightness adjustment on hover */
.product-brightness:hover {
    filter: brightness(1.1);
}

/* 3D effect on hover */
.product-3d:hover {
    transform: perspective(1000px) rotateY(5deg);
}

/* Blend Mode Effects for Product Images */
.product-image-blend {
    transition: all 0.4s ease;
    /* Basic blend */
    mix-blend-mode: multiply;
    background-color: #f8f9fa; /* Light background color to blend with */
}

/* Different blend mode options */
.blend-overlay {
    mix-blend-mode: overlay;
}

.blend-soft-light {
    mix-blend-mode: soft-light;
}

.blend-screen {
    mix-blend-mode: screen;
}

.blend-multiply {
    mix-blend-mode: multiply;
}

/* Background integration effect */
.product-bg-blend {
    position: relative;
    overflow: hidden;
    background-color: white;
}

.product-bg-blend img {
    mix-blend-mode: multiply;
    border-radius: 8px;
}
/* Special handling for transparent product images */
.product-image-transparent {
    transition: all 0.3s ease-in-out;
    /* No harsh blend mode needed since image already has transparency */
}

.product-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

/* Subtle hover effect appropriate for shoes */
.product-image-transparent:hover {
    transform: rotate(-8deg) scale(1.02);
    filter: drop-shadow(0 10px 15px rgba(30, 80, 200, 0.2));
}

/* Gradient background blend */
.gradient-blend {
    background: white;
    padding: 20px;
    border-radius: 12px;
}

.gradient-blend img {
    mix-blend-mode: multiply;
    transition: all 0.3s ease;
}

.gradient-blend:hover img {
    mix-blend-mode: normal;
}

/* Thumbnail gallery styling */
.thumbnail-container {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.thumbnail-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.thumbnail-image.active {
    border-color: #dc3545; /* Match your theme color */
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
    <!--   <!-- Navigation -->
     <nav>
        
            
        <ul>

        <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
        <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart <span id="cart-count">0</span></a></li>
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
    <div class="container my-5">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="breadcrumb-wrapper">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="product.php">Products</a>
            </li>
            <li class="breadcrumb-item">
                <a href="product.php?category=<?php echo $product['category_id']; ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($product['name']); ?>
            </li>
        </ol>
    </nav>
</div>

        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                         class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title h3 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        
                        <div class="my-3">
                            <h3 class="text-success">₹<?php echo number_format($product['base_price'], 2); ?></h3>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        
                        <?php if ($hasSizesInStock): ?>
                            <form method="post" action="" id="add-to-cart-form">
                                <div class="mb-4">
                                    <label class="form-label">Size</label>
                                    <div class="size-radio-group">
                                        <?php foreach ($sizes as $size): ?>
                                            <div>
                                                <input type="radio" 
                                                       id="size-<?php echo $size['id']; ?>" 
                                                       name="size_id" 
                                                       value="<?php echo $size['id']; ?>" 
                                                       class="size-radio" 
                                                       data-price="<?php echo $product['base_price'] + $size['price_adjustment']; ?>"
                                                       data-stock="<?php echo $size['stock']; ?>"
                                                       <?php echo $size['stock'] <= 0 ? 'disabled' : ''; ?>
                                                       required>
                                                <label for="size-<?php echo $size['id']; ?>" class="size-label">
                                                    <?php echo htmlspecialchars($size['size']); ?>
                                                    <?php if ($size['price_adjustment'] > 0): ?>
                                                        <div class="small">+₹<?php echo number_format($size['price_adjustment'], 2); ?></div>
                                                    <?php endif; ?>
                                                    <div class="small text-muted">
                                                        <?php echo $size['stock'] > 0 ? $size['stock'] . ' left' : 'Out of stock'; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="invalid-feedback" id="size-feedback">Please select a size</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <div class="quantity-selector">
                                        <button type="button" class="btn btn-outline-secondary" id="decrease-qty">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" id="quantity" class="form-control quantity-input" value="1" min="1" max="10" required>
                                        <button type="button" class="btn btn-outline-secondary" id="increase-qty">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="form-text" id="stock-info"></div>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 id="total-price">Total: ₹<?php echo number_format($product['base_price'], 2); ?></h4>
                                </div>
                                
                                <div class="d-grid gap-2">
    <button type="submit" name="add_to_cart" class="btn btn-red btn-lg">
        <i class="fas fa-shopping-cart me-2"></i> Add to Cart
    </button>
    <a href="product.php" class="btn btn-continue btn-lg">
        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
    </a>
</div>
<div class="col-md-6">
    <div class="card product-card-white">
        <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
             class="card-img-top shoe-product-image" 
             alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
</div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i> This product is currently out of stock.
                            </div>
                            <div class="d-grid">
                                <a href="product.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
        // Combined JavaScript for product image effects
document.addEventListener('DOMContentLoaded', function() {
    // Simple image gallery functionality
    const thumbnails = document.querySelectorAll('.thumbnail-image');
    if (thumbnails.length > 0) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const newSrc = this.getAttribute('src');
                const mainImage = document.querySelector('.main-product-image');
                if (mainImage) {
                    mainImage.setAttribute('src', newSrc);
                    
                    // Update active state
                    thumbnails.forEach(thumb => thumb.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
    }
    
    // Apply different blend modes based on image characteristics
    const productImages = document.querySelectorAll('.product-image');
    productImages.forEach(img => {
        img.addEventListener('load', function() {
            // For demonstration - in production you might use a canvas to analyze image brightness
            // This simplified approach uses image src to determine if it's likely a white background
            if (img.src.includes('white') || img.src.includes('light')) {
                img.classList.add('blend-soft-light');
            } else {
                img.classList.add('blend-multiply');
            }
        });
        
        // If image is already loaded when script runs
        if (img.complete) {
            if (img.src.includes('white') || img.src.includes('light')) {
                img.classList.add('blend-soft-light');
            } else {
                img.classList.add('blend-multiply');
            }
        }
    });
});
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const sizeRadios = document.querySelectorAll('.size-radio');
            const quantityInput = document.getElementById('quantity');
            const totalPriceElement = document.getElementById('total-price');
            const decreaseBtn = document.getElementById('decrease-qty');
            const increaseBtn = document.getElementById('increase-qty');
            const stockInfoElement = document.getElementById('stock-info');
            const addToCartForm = document.getElementById('add-to-cart-form');
            
            // Update quantity event handlers
            decreaseBtn?.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                    updateTotalPrice();
                }
            });
            
            increaseBtn?.addEventListener('click', function() {
                const currentValue = parseInt(quantityInput.value);
                const maxStock = getSelectedSizeStock();
                
                if (currentValue < 10 && currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                    updateTotalPrice();
                }
            });
            
            // Get selected size stock
            function getSelectedSizeStock() {
                const selectedSize = document.querySelector('.size-radio:checked');
                return selectedSize ? parseInt(selectedSize.dataset.stock) : 0;
            }
            
            // Update total price
            function updateTotalPrice() {
                const selectedSize = document.querySelector('.size-radio:checked');
                
                if (selectedSize) {
                    const price = parseFloat(selectedSize.dataset.price);
                    const quantity = parseInt(quantityInput.value);
                    const maxStock = parseInt(selectedSize.dataset.stock);
                    
                    // Update stock info
                    stockInfoElement.textContent = `${maxStock} available`;
                    
                    // Limit quantity to available stock
                    if (quantity > maxStock) {
                        quantityInput.value = maxStock;
                    }
                    
                    // Update total price
                    const total = price * parseInt(quantityInput.value);
                    totalPriceElement.textContent = `Total: $${total.toFixed(2)}`;
                }
            }
            
            // Size selection event handlers
            sizeRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    updateTotalPrice();
                });
            });
            
            // Quantity input event handler
            quantityInput?.addEventListener('input', function() {
                // Ensure quantity is a number and within valid range
                let value = parseInt(this.value) || 1;
                const maxStock = getSelectedSizeStock();
                
                if (value < 1) value = 1;
                if (value > 10) value = 10;
                if (value > maxStock) value = maxStock;
                
                this.value = value;
                updateTotalPrice();
            });
            
            // Form validation
            addToCartForm?.addEventListener('submit', function(e) {
                const selectedSize = document.querySelector('.size-radio:checked');
                
                if (!selectedSize) {
                    e.preventDefault();
                    document.getElementById('size-feedback').style.display = 'block';
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
