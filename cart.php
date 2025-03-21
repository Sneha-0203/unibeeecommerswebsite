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
    <title>Shopping Cart - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Shoe Store</a>
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
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative active">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($itemCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $itemCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="orderHistory.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

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
                    <a href="products.php" class="btn btn-primary mt-3">
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
                                                    <div class="fw-bold"><?php echo '$' . number_format($item['item_total'], 2); ?></div>
                                                    <div class="text-muted small"><?php echo '$' . number_format($item['item_price'], 2); ?> each</div>
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
                            <a href="products.php" class="btn btn-outline-secondary">
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
                                <span class="fw-bold"><?php echo '$' . number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (7%)</span>
                                <span><?php echo '$' . number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-5"><?php echo '$' . number_format($total, 2); ?></span>
                            </div>
                            <div class="d-grid">
                                <a href="checkout.php" class="btn btn-primary">
                                    <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">Need Help?</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <i class="fas fa-truck text-primary me-2"></i>
                                Free shipping on orders over $75
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-undo text-primary me-2"></i>
                                Free 30-day returns
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-headset text-primary me-2"></i>
                                Have questions? <a href="contact.php">Contact us</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Shoe Store</h5>
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
                        <p><i class="fas fa-envelope me-2"></i> info@shoestore.com</p>
                    </address>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Shoe Store. All rights reserved.</p>
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