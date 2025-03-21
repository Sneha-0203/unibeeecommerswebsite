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
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch user's orders with proper prepared statement
$ordersQuery = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$ordersStmt = $conn->prepare($ordersQuery);
if ($ordersStmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();

// Get cart count - same as in products.php
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;

// Process order cancellation if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = $_POST['order_id'];
    
    // Only allow cancellation of pending or processing orders
    $checkStmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $orderId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $orderStatus = $checkResult->fetch_assoc();
    
    if ($orderStatus && ($orderStatus['status'] === 'pending' || $orderStatus['status'] === 'processing')) {
        $cancelStmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $cancelStmt->bind_param("ii", $orderId, $userId);
        $cancelStmt->execute();
        
        // Redirect to refresh the page
        header('Location: orderHistory.php?cancelled=' . $orderId);
        exit();
    }
}

// Function to get order items
function getOrderItems($conn, $orderId) {
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    
    return $items;
}

// Helper functions
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'processing':
            return 'bg-info text-dark';
        case 'shipped':
            return 'bg-primary';
        case 'delivered':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function formatPaymentMethod($method) {
    switch ($method) {
        case 'credit_card':
            return 'Credit Card';
        case 'cod':
            return 'Cash on Delivery';
        default:
            return ucfirst($method);
    }
}

function canCancelOrder($status) {
    return ($status === 'pending' || $status === 'processing');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .cancel-btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        .order-items {
            font-size: 0.85rem;
            max-width: 300px;
        }
        /* Footer link hover effect */
        footer a.text-white:hover {
            color: #f8f9fa !important;
            text-decoration: underline;
        }
        tr.order-row {
            cursor: pointer;
        }
        tr.order-details {
            background-color: #f8f9fa;
        }
        .address-details {
            font-size: 0.85rem;
            color: #666;
        }
        .item-details {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .item-details:last-child {
            border-bottom: none;
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
                        <a class="nav-link active" href="orderHistory.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo htmlspecialchars($cartCount); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
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
                <h2>Your Order History</h2>
                <p class="text-muted">Track and manage all your purchases</p>
            </div>
        </div>

        <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Order #<?php echo htmlspecialchars($_GET['cancelled']); ?> has been successfully cancelled.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Shipping Address</th>
                                    <th>Items</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $ordersResult->fetch_assoc()): 
                                    $orderItems = getOrderItems($conn, $order['id']);
                                ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($order['status']); ?> status-badge">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(formatPaymentMethod($order['payment_method'])); ?></td>
                                        <td class="address-details">
                                            <?php 
                                                $address = htmlspecialchars($order['shipping_address'] ?? 'N/A');
                                                echo (strlen($address) > 30) ? substr($address, 0, 30) . '...' : $address; 
                                            ?>
                                        </td>
                                        <td>
                                            <div class="order-items">
                                                <?php foreach($orderItems as $index => $item): ?>
                                                    <div class="item-details">
                                                        <strong>Product ID:</strong> <?php echo htmlspecialchars($item['product_id']); ?><br>
                                                        <strong>Size:</strong> <?php echo htmlspecialchars($item['product_size_id']); ?><br>
                                                        <strong>Qty:</strong> <?php echo htmlspecialchars($item['quantity']); ?><br>
                                                        <strong>Price:</strong> $<?php echo htmlspecialchars($item['price']); ?>
                                                    </div>
                                                    <?php if ($index >= 2): ?>
                                                        <div class="text-muted">+ <?php echo count($orderItems) - 3; ?> more items</div>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (canCancelOrder($order['status'])): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" name="cancel_order" class="btn btn-danger btn-sm cancel-btn">
                                                        <i class="fas fa-times me-1"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm cancel-btn" disabled>
                                                    <?php echo ($order['status'] === 'cancelled') ? 'Cancelled' : 'Cannot Cancel'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't placed any orders yet.
                    </div>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
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
                        <p><i class="fas fa-envelope me-2"></i> info@shoestore.com</p>
                    </address>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Shoe Store. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optional JavaScript for enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Any additional JavaScript functionality would go here
        });
    </script>
</body>
</html>