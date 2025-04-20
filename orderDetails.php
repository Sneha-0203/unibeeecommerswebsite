<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to view order details.";
    header("Location: login.php");
    exit();
}

// Get current user ID from session
$userId = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header("Location: user-order_history.php");
    exit();
}

$orderId = $_GET['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shoe_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$orderQuery = "SELECT o.id, o.created_at, o.total_amount, o.status, o.shipping_address, o.payment_method
               FROM orders o
               WHERE o.id = ? AND o.user_id = ?";

$stmt = $conn->prepare($orderQuery);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found or you don't have permission to view it.";
    header("Location: user-order_history.php");
    exit();
}

$orderDetails = $orderResult->fetch_assoc();

// Get order items
$itemsQuery = "SELECT oi.product_id, ps.size as size, oi.quantity, oi.price, p.name, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_sizes ps ON oi.product_size_id = ps.id
                WHERE oi.order_id = ?";

$stmt = $conn->prepare($itemsQuery);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $orderId);
$stmt->execute();
$itemsResult = $stmt->get_result();

$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
// Check if delete action is triggered
if (isset($_POST['delete_order']) && $_POST['delete_order'] == 'true') {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete related order items first
        $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($deleteItemsQuery);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // Then delete the order itself
        $deleteOrderQuery = "DELETE FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($deleteOrderQuery);
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        
        // Check if order was actually deleted (means user had permission)
        if ($stmt->affected_rows > 0) {
            // Commit the transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Order #$orderId has been successfully deleted.";
            header("Location: user-order_history.php");
            exit();
        } else {
            // If no rows affected, order not found or no permission
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to delete the order. Order not found or permission denied.";
            header("Location: user-order_history.php");
            exit();
        }
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $_SESSION['error_message'] = "An error occurred while deleting the order: " . $e->getMessage();
        header("Location: user-order_history.php");
        exit();
    }
}
// Close statement and connection when done
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - UNIBEE</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #ff006e;
            --accent-color: #fb5607;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        nav {
            background: linear-gradient(to right, #4a90e2, #ffcc33);
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
            color: #4a90e2;
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
            margin-top: 90%;
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
            background-color: #f9f9f9;
            color: #4a90e2;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
            color: #4a90e2;
        }
        
        .order-header {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .order-id {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .order-date {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .product-details {
            font-size: 0.9rem;
            color: #666;
        }
        
        .status-badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
            display: inline-block;
        }
        
        .shipment-info {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
        }
        
        .tracking-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .tracking-link:hover {
            text-decoration: underline;
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 1.2rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-color);
            left: 50%;
            margin-left: -1.5px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 50%;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: white;
            border: 4px solid var(--primary-color);
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-left {
            left: 0;
        }
        
        .timeline-right {
            left: 50%;
        }
        
        .timeline-left::after {
            right: -10px;
        }
        
        .timeline-right::after {
            left: -10px;
        }
        
        .timeline-content {
            padding: 15px;
            background-color: white;
            position: relative;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .timeline-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .timeline::before {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-left::after, .timeline-right::after {
                left: 21px;
            }
            
            .timeline-right {
                left: 0%;
            }
        }
        
        footer {
            background: #000;
            color: #fff;
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
            color: #fff;
            text-decoration: underline;
            text-decoration-thickness: 3px;
            text-underline-offset: 5px;
            text-decoration-color: #ff9800;
            transition: color 0.3s ease, text-decoration-color 0.3s ease;
        }

        .footer-col h3:hover {
            color: #ff9800;
            text-decoration-color: #fff;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            font-size: 18px;
            color: #ccc;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-col ul li a:hover {
            color: #ff9800;
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

    <nav>
        <ul>
            <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
            <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="user-order_history.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>

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

    <div class="container mt-4">
        <!-- Flash Messages/Alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Order Detail Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0"><i class="fas fa-shopping-bag text-primary me-2"></i>Order Details</h2>
                        <p class="text-muted mb-0">View the details of your order</p>
                    </div>
                    <a href="user-order_history.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="row">
            <div class="col-md-8">
                <!-- Order Info Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="fas fa-info-circle me-2"></i>Order Information</span>
                        <span class="order-id">#<?php echo $orderDetails['id']; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Date:</strong></p>
                                <p><?php echo date('F d, Y h:i A', strtotime($orderDetails['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Order Status:</strong></p>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                switch(strtolower($orderDetails['status'])) {
                                    case 'pending':
                                        $statusClass = 'bg-warning text-dark';
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'processing':
                                        $statusClass = 'bg-info text-dark';
                                        $statusIcon = 'fa-cog fa-spin';
                                        break;
                                    case 'shipped':
                                        $statusClass = 'bg-primary';
                                        $statusIcon = 'fa-truck';
                                        break;
                                    case 'delivered':
                                        $statusClass = 'bg-success';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-danger';
                                        $statusIcon = 'fa-times-circle';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                        $statusIcon = 'fa-question-circle';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                    <?php echo ucfirst($orderDetails['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Payment Method:</strong></p>
                                <p>
                                    <?php if($orderDetails['payment_method'] == 'credit_card'): ?>
                                        <i class="far fa-credit-card me-1"></i> Credit Card
                                    <?php elseif($orderDetails['payment_method'] == 'paypal'): ?>
                                        <i class="fab fa-paypal me-1"></i> PayPal
                                    <?php elseif($orderDetails['payment_method'] == 'cash_on_delivery'): ?>
                                        <i class="fas fa-money-bill me-1"></i> Cash on Delivery
                                    <?php else: ?>
                                        <?php echo $orderDetails['payment_method']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Total Amount:</strong></p>
                                <p class="text-primary fw-bold">₹<?php echo number_format($orderDetails['total_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-map-marker-alt me-2"></i>Shipping Information
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($orderDetails['shipping_address'])); ?></p>
                        
                        <?php if(!empty($orderDetails['tracking_number']) && strtolower($orderDetails['status']) != 'pending' && strtolower($orderDetails['status']) != 'processing'): ?>
                        <div class="shipment-info mt-3">
                            <p class="mb-1"><strong><i class="fas fa-truck me-1"></i> Tracking Information:</strong></p>
                            <p class="mb-0">Tracking Number: <span class="fw-bold"><?php echo $orderDetails['tracking_number']; ?></span></p>
                            <small class="text-muted">You can track your package using this number on the courier's website.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-box-open me-2"></i>Order Items
                    </div>
                    <div class="card-body">
                        <?php if(!empty($orderItems)): ?>
                            <?php foreach($orderItems as $item): ?>
                            <div class="row mb-4 pb-3 border-bottom">
                                <div class="col-md-2 col-3">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                </div>
                                <div class="col-md-6 col-9">
                                    <h5 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="product-details">
                                        Size: <?php echo htmlspecialchars($item['size']); ?><br>
                                        Quantity: <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 col-12 mt-2 mt-md-0 text-md-end">
                                    <p class="mb-0">Price per item: <span class="fw-bold">₹<?php echo number_format($item['price'], 2); ?></span></p>
                                    <p class="fw-bold text-primary">Subtotal: ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p>No items found in this order.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Order Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate subtotals
                        $subtotal = 0;
                        foreach($orderItems as $item) {
                            $subtotal += ($item['price'] * $item['quantity']);
                        }
                        // Assuming tax rate of 5% and shipping of ₹100
                        $taxRate = 0.05;
                        $tax = $subtotal * $taxRate;
                        $shipping = 100;
                        $total = $subtotal + $tax + $shipping;
                        ?>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (5%):</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>₹<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>₹<?php echo number_format($orderDetails['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Timeline -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Order Timeline
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3">
                            <?php
                            // Simulating order timeline based on order status
                            $currentStatus = strtolower($orderDetails['status']);
                            $statusTimeline = [
                                'pending' => ['date' => date('Y-m-d H:i:s', strtotime($orderDetails['created_at'])), 'completed' => true],
                                'processing' => ['date' => date('Y-m-d H:i:s', strtotime($orderDetails['created_at'] . ' +1 day')), 'completed' => $currentStatus != 'pending'],
                                'shipped' => ['date' => date('Y-m-d H:i:s', strtotime($orderDetails['created_at'] . ' +3 days')), 'completed' => in_array($currentStatus, ['shipped', 'delivered'])],
                                'delivered' => ['date' => date('Y-m-d H:i:s', strtotime($orderDetails['created_at'] . ' +5 days')), 'completed' => $currentStatus == 'delivered']
                            ];
                            
                            if($currentStatus == 'cancelled') {
                                // Special case for cancelled orders
                                $cancelDate = date('Y-m-d H:i:s', strtotime($orderDetails['created_at'] . ' +1 day'));
                            }
                            ?>
                            
                            <ul class="list-group list-group-flush">
                                <?php if($currentStatus == 'cancelled'): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-circle text-success me-3"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Order Placed</h6>
                                                <p class="small text-muted mb-0"><?php echo date('F d, Y h:i A', strtotime($orderDetails['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-circle text-danger me-3"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Order Cancelled</h6>
                                                <p class="small text-muted mb-0"><?php echo date('F d, Y h:i A', strtotime($cancelDate)); ?></p>
                                            </div>
                                        </div>
                                    </li>
                                <?php else: ?>
                                    <?php foreach($statusTimeline as $status => $info): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <?php if($info['completed']): ?>
                                                        <i class="fas fa-circle text-success me-3"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-circle text-secondary me-3"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">Order <?php echo ucfirst($status); ?></h6>
                                                    <?php if($info['completed']): ?>
                                                        <p class="small text-muted mb-0"><?php echo date('F d, Y h:i A', strtotime($info['date'])); ?></p>
                                                    <?php else: ?>
                                                        <p class="small text-muted mb-0">Pending</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-4">
                    <?php if($currentStatus != 'cancelled'): ?>
                        <?php if($currentStatus == 'delivered'): ?>
                            <a href="#" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-star me-1"></i> Review Products
                            </a>
                        <?php endif; ?>
                        
                        <?php if($currentStatus == 'pending'): ?>
    <form method="POST" onsubmit="return confirmDelete()">
        <input type="hidden" name="delete_order" value="true">
        <button type="submit" class="btn btn-outline-danger w-100 mb-2">
            <i class="fas fa-trash me-1"></i> Delete Order
        </button>
    </form>
<?php endif; ?>
                        
                        <a href="#" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-question-circle me-1"></i> Need Help?
                        </a>
                    <?php else: ?>
                        <a href="#" class="btn btn-outline-primary w-100">
                            <i class="fas fa-shopping-cart me-1"></i> Shop Again
                        </a>
                    <?php endif; ?>
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
    </footer>    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function confirmDelete() {
    return confirm('Are you sure you want to delete this order? This action cannot be undone.');
}
</script>
</body>
</html>

<?php
// Close database connection


?>