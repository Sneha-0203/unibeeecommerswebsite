
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

// Handle cart updates
$message = '';
$messageType = '';

// Remove item from cart
if (isset($_POST['remove_item']) && isset($_POST['cart_id']) && is_numeric($_POST['cart_id'])) {
    $cartId = (int)$_POST['cart_id'];
    
    // Verify the cart item belongs to the user
    $verifyQuery = "SELECT * FROM cart WHERE id = ? AND user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bind_param("ii", $cartId, $userId);
    $verifyStmt->execute();
    
    if ($verifyStmt->get_result()->num_rows > 0) {
        // Delete the cart item
        $deleteQuery = "DELETE FROM cart WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $cartId);
        
        if ($deleteStmt->execute()) {
            $message = "Item removed from cart.";
            $messageType = "success";
        } else {
            $message = "Failed to remove item. Please try again.";
            $messageType = "danger";
        }
    }
}

// Update item quantity
if (isset($_POST['update_quantity']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cartId = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1;
    } else if ($quantity > 10) {
        $quantity = 10;
    }
    
    // Verify the cart item belongs to the user and check stock
    $verifyQuery = "SELECT c.*, ps.stock 
                    FROM cart c 
                    JOIN product_sizes ps ON c.product_size_id = ps.id 
                    WHERE c.id = ? AND c.user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bind_param("ii", $cartId, $userId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $cartItem = $verifyResult->fetch_assoc();
        
        // Check if requested quantity exceeds available stock
        if ($quantity > $cartItem['stock']) {
            $message = "Requested quantity exceeds available stock. Maximum available: " . $cartItem['stock'];
            $messageType = "warning";
            $quantity = $cartItem['stock']; // Set to maximum available
        }
        
        // Update the cart item quantity
        $updateQuery = "UPDATE cart SET quantity = ?, created_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $quantity, $cartId);
        
        if ($updateStmt->execute()) {
            $message = "Cart updated successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to update cart. Please try again.";
            $messageType = "danger";
        }
    }
}

// Clear entire cart
if (isset($_POST['clear_cart'])) {
    $clearQuery = "DELETE FROM cart WHERE user_id = ?";
    $clearStmt = $conn->prepare($clearQuery);
    $clearStmt->bind_param("i", $userId);
    
    if ($clearStmt->execute()) {
        $message = "Your cart has been cleared.";
        $messageType = "success";
    } else {
        $message = "Failed to clear cart. Please try again.";
        $messageType = "danger";
    }
}

// Get cart items with product details
$cartQuery = "SELECT c.id, c.quantity, c.product_id, c.product_size_id, 
              p.name as product_name, p.image, p.base_price,
              ps.size, ps.price_adjustment, ps.stock
              FROM cart c
              JOIN products p ON c.product_id = p.id
              JOIN product_sizes ps ON c.product_size_id = ps.id
              WHERE c.user_id = ?
              ORDER BY c.created_at DESC";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartItems = $cartStmt->get_result();

// Calculate cart totals
$subtotal = 0;
$itemCount = 0;
$cartData = [];

// Process cart items
while ($item = $cartItems->fetch_assoc()) {
    $itemPrice = $item['base_price'] + $item['price_adjustment'];
    $itemTotal = $itemPrice * $item['quantity'];
    $subtotal += $itemTotal;
    $itemCount += $item['quantity'];
    
    // Add calculated values to item data
    $item['item_price'] = $itemPrice;
    $item['item_total'] = $itemTotal;
    $cartData[] = $item;
}

// Calculate tax and total
$taxRate = 0.07; // 7% tax rate
$tax = $subtotal * $taxRate;
$total = $subtotal + $tax;

// Check if cart is empty
$cartIsEmpty = empty($cartData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - UNIBEE</title>
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
    background-color: #FF5500;
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
    background-color:rgb(94, 92, 92);
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
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            max-width: 120px;
        }
        .quantity-selector button {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .quantity-input {
            width: 40px;
            text-align: center;
        }
        .item-price {
            min-width: 100px;
            text-align: right;
        }
        .help-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.help-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
}

.help-card .card-header {
    background-color: transparent;
    font-weight: bold;
    font-size: 18px;
    border-bottom: none;
}

.help-card .card-body p {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    transition: color 0.3s ease;
    font-size: 15.5px;
}

.help-card .card-body p i {
    color: #FF5500; /* Orange icons */
    font-size: 18px;
    margin-right: 10px;
    transition: transform 0.3s ease, color 0.3s ease;
}

.help-card .card-body p:hover i {
    transform: scale(1.2);
    color: #e84300;
}

.help-card .card-body a {
    color: #FF5500;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.help-card .card-body a:hover {
    color: #e84300;
    text-decoration: underline;
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

        .social-links a:nth-child(1) i { color: #FF5500; }
        .social-links a:nth-child(2) i { color: #FF5500 }
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
        </div>
    </header>   

    <!-- Main Content -->
    <div class="container my-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($cartIsEmpty): ?>
            <div class="card shadow-sm">
                <div class="card-body py-5 text-center">
                    <i class="fas fa-shopping-cart fa-4x mb-4 text-muted"></i>
                    <h3>Your cart is empty</h3>
                    <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
                    <a href="product.php" class="btn btn-primary mt-3">
                        <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cart Items (<?php echo $itemCount; ?>)</h5>
                            <form method="post" onsubmit="return confirm('Are you sure you want to clear your cart?');">
                                <button type="submit" name="clear_cart" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt me-1"></i> Clear Cart
                                </button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 120px">Product</th>
                                            <th scope="col">Details</th>
                                            <th scope="col" style="width: 150px">Quantity</th>
                                            <th scope="col" class="text-end" style="width: 120px">Price</th>
                                            <th scope="col" style="width: 50px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartData as $item): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo !empty($item['image']) ? 'uploads/products/' . htmlspecialchars($item['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                                                         class="cart-item-image" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                </td>
                                                <td>
                                                    <h6 class="mb-1">
                                                        <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                                        </a>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                                    <?php if ($item['quantity'] > $item['stock']): ?>
                                                        <p class="mb-0 text-danger small">
                                                            <i class="fas fa-exclamation-circle me-1"></i> 
                                                            Only <?php echo $item['stock']; ?> available
                                                        </p>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="post" class="update-quantity-form">
                                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                        <div class="quantity-selector">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary decrease-qty">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <input type="number" name="quantity" class="form-control form-control-sm quantity-input" 
                                                                   value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min(10, $item['stock']); ?>" 
                                                                   data-stock="<?php echo $item['stock']; ?>" required>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary increase-qty">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary mt-2 update-btn" style="display: none;">
                                                            Update
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-end">
                                                    <div class="fw-bold"><?php echo '₹' . number_format($item['item_total'], 2); ?></div>
                                                    <div class="text-muted small"><?php echo '₹' . number_format($item['item_price'], 2); ?> each</div>
                                                </td>
                                                <td>
                                                    <form method="post" onsubmit="return confirm('Remove this item from cart?');">
                                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="remove_item" class="btn btn-sm text-danger border-0">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="product.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span class="fw-bold"><?php echo '₹' . number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (7%)</span>
                                <span><?php echo '₹' . number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-5"><?php echo '₹' . number_format($total, 2); ?></span>
                            </div>
                            <div class="d-grid">
                                <a href="checkout.php" class="btn btn-primary">
                                    <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm help-card">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Need Help?</h5>
    </div>
    <div class="card-body">
        <p class="mb-2">
            <i class="fas fa-truck me-2"></i>
            Free shipping on orders over Rs-75
        </p>
        <p class="mb-2">
            <i class="fas fa-undo me-2"></i>
            Free 30-day returns
        </p>
        <p class="mb-0">
            <i class="fas fa-headset me-2"></i>
            Have questions? <a href="contact.php">Contact us</a>
        </p>
    </div>
</div>

                </div>
            </div>
        <?php endif; ?>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity selector functionality
            const quantityForms = document.querySelectorAll('.update-quantity-form');
            
            quantityForms.forEach(form => {
                const quantityInput = form.querySelector('.quantity-input');
                const decreaseBtn = form.querySelector('.decrease-qty');
                const increaseBtn = form.querySelector('.increase-qty');
                const updateBtn = form.querySelector('.update-btn');
                const originalValue = parseInt(quantityInput.value);
                const maxStock = parseInt(quantityInput.dataset.stock);
                
                // Show update button when quantity changes
                function checkValueChanged() {
                    const currentValue = parseInt(quantityInput.value);
                    if (currentValue !== originalValue) {
                        updateBtn.style.display = 'inline-block';
                    } else {
                        updateBtn.style.display = 'none';
                    }
                }
                
                // Decrease quantity
                decreaseBtn.addEventListener('click', function() {
                    const currentValue = parseInt(quantityInput.value);
                    if (currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                        checkValueChanged();
                    }
                });
                
                // Increase quantity
                increaseBtn.addEventListener('click', function() {
                    const currentValue = parseInt(quantityInput.value);
                    if (currentValue < 10 && currentValue < maxStock) {
                        quantityInput.value = currentValue + 1;
                        checkValueChanged();
                    }
                });
                
                // Manual input change
                quantityInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || 1;
                    
                    if (value < 1) value = 1;
                    if (value > 10) value = 10;
                    if (value > maxStock) value = maxStock;
                    
                    this.value = value;
                    checkValueChanged();
                });
            });
        });
    </script>
</body>
</html>
