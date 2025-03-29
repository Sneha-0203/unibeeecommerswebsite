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
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cartCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
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
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
        
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
                            <h3 class="text-success">$<?php echo number_format($product['base_price'], 2); ?></h3>
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
                                                        <div class="small">+$<?php echo number_format($size['price_adjustment'], 2); ?></div>
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
                                    <h4 id="total-price">Total: $<?php echo number_format($product['base_price'], 2); ?></h4>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                                    </button>
                                    <a href="products.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i> This product is currently out of stock.
                            </div>
                            <div class="d-grid">
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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