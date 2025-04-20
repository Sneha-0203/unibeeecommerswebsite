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

// Check if cart is empty
if ($cartItems->num_rows === 0) {
    header('Location: cart.php');
    exit();
}

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
$taxRate = 0.18; // 18% GST for India
$tax = $subtotal * $taxRate;
$total = $subtotal + $tax;

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip = $_POST['zip'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $card_number = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : '';
    $card_holder = $_POST['card_holder'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Simple validation
    if (empty($name) || empty($email) || empty($address) || empty($city) || empty($state) || empty($zip) || empty($payment_method)) {
        $message = "All fields are required";
        $messageType = "danger";
    } elseif ($payment_method === 'credit_card' && (empty($card_number) || empty($card_holder) || empty($expiry_date) || empty($cvv))) {
        $message = "All card details are required for card payment";
        $messageType = "danger";
    } else {
        try {
            // Begin transaction
            $conn->begin_transaction();
            
            // Create shipping address
            $shipping_address = "$name\n$address\n$city, $state $zip\nIndia";
            
            // Create order
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
            $status = 'pending';
            $orderStmt->bind_param("idsss", $userId, $total, $shipping_address, $payment_method, $status);
            $orderStmt->execute();
            $orderId = $conn->insert_id;
            
            // Add order items
            foreach ($cartData as $item) {
                $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_size_id, quantity, price) 
                                        VALUES (?, ?, ?, ?, ?)");
                $itemStmt->bind_param("iiiid", $orderId, $item['product_id'], $item['product_size_id'], $item['quantity'], $item['item_price']);
                $itemStmt->execute();
            }
            
            // Clear user's cart
            $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearStmt->bind_param("i", $userId);
            $clearStmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Save order ID in session for confirmation page
            $_SESSION['order_id'] = $orderId;
            
            // Redirect to order confirmation
            header('Location: order-confirmation.php');
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "An error occurred while processing your order. Please try again.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-item-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .form-section {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .payment-method-option {
            display: block;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .payment-method-option:hover {
            border-color: #adb5bd;
        }
        .payment-method-option.selected {
            border-color: #4a90e2;
            background-color: #f0f7ff;
        }
        .card-form {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        .card-form.active {
            display: block;
        }
        .order-summary-card {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .gst-note {
            font-size: 0.8rem;
            color: #6c757d;
        }
        /* Indian state selector styles */
        .form-select.indian-states {
            background-color: #f8f9fa;
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
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative">
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
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="post" id="checkout-form">
                    <!-- Shipping Information -->
                    <div class="form-section">
                        <h3 class="mb-3">Shipping Information</h3>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="address" name="address" required placeholder="House No., Building Name, Street">
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="state" class="form-label">State</label>
                                <select class="form-select indian-states" id="state" name="state" required>
                                    <option value="">Select State</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                    <option value="Assam">Assam</option>
                                    <option value="Bihar">Bihar</option>
                                    <option value="Chhattisgarh">Chhattisgarh</option>
                                    <option value="Goa">Goa</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Himachal Pradesh">Himachal Pradesh</option>
                                    <option value="Jharkhand">Jharkhand</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Madhya Pradesh">Madhya Pradesh</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Manipur">Manipur</option>
                                    <option value="Meghalaya">Meghalaya</option>
                                    <option value="Mizoram">Mizoram</option>
                                    <option value="Nagaland">Nagaland</option>
                                    <option value="Odisha">Odisha</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Sikkim">Sikkim</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Tripura">Tripura</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="Uttarakhand">Uttarakhand</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                    <option value="Ladakh">Ladakh</option>
                                    <option value="Puducherry">Puducherry</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="zip" class="form-label">PIN Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" required placeholder="6 digits">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control bg-light" id="country" name="country" value="India" readonly>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3 class="mb-3">Payment Method</h3>
                        
                        <div class="mb-3">
                            <div class="payment-method-option" id="card-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                    <label class="form-check-label d-flex align-items-center" for="credit_card">
                                        <i class="fas fa-credit-card me-2 text-primary"></i>
                                        Credit/Debit Card
                                    </label>
                                </div>
                            </div>
                            
                            <div id="card-form" class="card-form">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="card_holder" class="form-label">Card Holder Name</label>
                                        <input type="text" class="form-control" id="card_holder" name="card_holder">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="password" class="form-control" id="cvv" name="cvv" placeholder="123">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method-option" id="cod-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                                    <label class="form-check-label d-flex align-items-center" for="cod">
                                        <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                        Cash on Delivery (COD)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="cart.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Return to Cart
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-lock me-2"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card shadow-sm mb-4 order-summary-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <?php foreach ($cartData as $item): ?>
                                <div class="d-flex mb-3">
                                    <img src="<?php echo !empty($item['image']) ? 'uploads/products/' . htmlspecialchars($item['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                                         class="checkout-item-image me-3" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                        <p class="mb-1 text-muted small">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                        <p class="mb-0 small">
                                            <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['item_price'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span class="fw-bold">₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>GST (18%)</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <p class="gst-note">GST Registration: GSTIN123456789</p>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold fs-5 text-primary">₹<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Support -->
                <div class="card shadow-sm order-summary-card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <i class="fas fa-truck text-primary me-2"></i>
                            Free shipping on orders over ₹2000
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-undo text-primary me-2"></i>
                            Easy 7-day returns
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-headset text-primary me-2"></i>
                            Have questions? <a href="contact.php">Contact us</a>
                        </p>
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
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Fashion Street, Mumbai, India</p>
                        <p><i class="fas fa-phone me-2"></i> +91 98765 43210</p>
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
            // Payment method selection
            const paymentOptions = document.querySelectorAll('.payment-method-option');
            const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
            const cardForm = document.getElementById('card-form');
            
            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remove 'selected' class from all options
                    paymentOptions.forEach(option => {
                        option.classList.remove('selected');
                    });
                    
                    // Add 'selected' class to parent of checked radio
                    if (this.checked) {
                        this.closest('.payment-method-option').classList.add('selected');
                        
                        // Toggle card form visibility
                        if (this.value === 'credit_card') {
                            cardForm.classList.add('active');
                            // Make card fields required
                            document.getElementById('card_number').setAttribute('required', '');
                            document.getElementById('card_holder').setAttribute('required', '');
                            document.getElementById('expiry_date').setAttribute('required', '');
                            document.getElementById('cvv').setAttribute('required', '');
                        } else {
                            cardForm.classList.remove('active');
                            // Remove required attribute from card fields
                            document.getElementById('card_number').removeAttribute('required');
                            document.getElementById('card_holder').removeAttribute('required');
                            document.getElementById('expiry_date').removeAttribute('required');
                            document.getElementById('cvv').removeAttribute('required');
                        }
                    }
                });
            });
            
            // Form validation
            const form = document.getElementById('checkout-form');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                // Email validation
                const emailField = document.getElementById('email');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (emailField && emailField.value.trim() && !emailRegex.test(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                }
                
                // PIN code validation for India (6 digits)
                const zipField = document.getElementById('zip');
                const zipRegex = /^[0-9]{6}$/;
                
                if (zipField && zipField.value.trim() && !zipRegex.test(zipField.value)) {
                    isValid = false;
                    zipField.classList.add('is-invalid');
                    alert('Please enter a valid 6-digit PIN code.');
                }
                
                // Card validation if credit card is selected
                const creditCardRadio = document.getElementById('credit_card');
                if (creditCardRadio.checked) {
                    // Card number validation (16 digits, can have spaces)
                    const cardNumberField = document.getElementById('card_number');
                    const cardNumberValue = cardNumberField.value.replace(/\s/g, '');
                    const cardNumberRegex = /^[0-9]{16}$/;
                    
                    if (!cardNumberRegex.test(cardNumberValue)) {
                        isValid = false;
                        cardNumberField.classList.add('is-invalid');
                        alert('Please enter a valid 16-digit card number.');
                    }
                    
                    // Expiry date validation (MM/YY format)
                    const expiryField = document.getElementById('expiry_date');
                    const expiryRegex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
                    
                    if (!expiryRegex.test(expiryField.value)) {
                        isValid = false;
                        expiryField.classList.add('is-invalid');
                        alert('Please enter a valid expiry date in MM/YY format.');
                    }
                    
                    // CVV validation (3 or 4 digits)
                    const cvvField = document.getElementById('cvv');
                    const cvvRegex = /^[0-9]{3,4}$/;
                    
                    if (!cvvRegex.test(cvvField.value)) {
                        isValid = false;
                        cvvField.classList.add('is-invalid');
                        alert('Please enter a valid 3 or 4 digit CVV.');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    if (!document.querySelector('.alert')) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.setAttribute('role', 'alert');
                        alertDiv.innerHTML = 'Please check the form for errors and fill in all required fields.';
                        alertDiv.innerHTML += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        form.prepend(alertDiv);
                    }
                }
            });
            
            // Format card number with spaces
            const cardNumberInput = document.getElementById('card_number');
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue;
            });
            
            // Format expiry date with slash
            const expiryInput = document.getElementById('expiry_date');
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
        });
    </script>
</body>
</html>