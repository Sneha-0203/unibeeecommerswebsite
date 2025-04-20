<?php
session_start();
require_once 'config/database.php';

// Suppress error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL parameter
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header('Location: admin_orders.php');
    exit();
}

// Get order details
try {
    // Get basic order info
    $order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   WHERE o.id = ?";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Order not found.";
        header('Location: admin_orders.php');
        exit();
    }
    
    $order = $result->fetch_assoc();
    
    // Get order items
    $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image 
                   FROM order_items oi
                   JOIN products p ON oi.product_id = p.id
                   WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $order_items = [];
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }

    // Process status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order status updated successfully.";
            // Update the order status in our local variable
            $order['status'] = $new_status;
        } else {
            $error_message = "Failed to update order status: " . $conn->error;
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "An error occurred while retrieving order details: " . $e->getMessage();
    header('Location: admin_orders.php');
    exit();
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar a {
            color: #f8f9fa;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #343a40;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-processing {
            background-color: #17a2b8;
            color: white;
        }
        .badge-shipped {
            background-color: #007bff;
            color: white;
        }
        .badge-delivered {
            background-color: #28a745;
            color: white;
        }
        .badge-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .product-img {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .timeline-item {
            position: relative;
            padding-left: 45px;
            padding-bottom: 20px;
        }
        .timeline-item:before {
            content: "";
            position: absolute;
            left: 15px;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item:last-child:before {
            height: 50%;
        }
        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            background-color: #007bff;
            color: #fff;
            z-index: 1;
        }
        .timeline-date {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .content {
                margin-left: 0;
            }
            .sidebar .d-flex {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 250px; height: 100%;">
                    <a href="admin_dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="fas fa-shoe-prints me-2"></i>
                        <span class="fs-4">Shoe Store Admin</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="admin_dashboard.php" class="nav-link text-white">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="admin_products.php" class="nav-link text-white">
                                <i class="fas fa-shoe-prints me-2"></i>
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="admin_categories.php" class="nav-link text-white">
                                <i class="fas fa-tags me-2"></i>
                                Categories
                            </a>
                        </li>
                        <li>
                            <a href="admin_orders.php" class="nav-link active" aria-current="page">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders
                            </a>
                        </li>
                        <li>
                            <a href="admin_users.php" class="nav-link text-white">
                                <i class="fas fa-users me-2"></i>
                                Users
                            </a>
                        </li>
                        <li>
                            <a href="admin_reports.php" class="nav-link text-white">
                                <i class="fas fa-chart-bar me-2"></i>
                                Reports
                            </a>
                        </li>
                        
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2 fs-5"></i>
                            <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="admin_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-clipboard-list me-2"></i> Order #<?php echo $order_id; ?> Details</h2>
                    <div>
                        <a href="admin_orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Orders
                        </a>
                        <button class="btn btn-primary ms-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print Order
                        </button>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Order Details Column -->
                    <div class="col-lg-8">
                        <!-- Order Info Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Order Information</h5>
                                <span class="badge badge-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
                                        <p class="mb-1"><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                                        <p class="mb-1"><strong>Customer Email:</strong> <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></p>
                                        <p class="mb-1"><strong>Customer ID:</strong> <?php echo htmlspecialchars($order['user_id']); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="mt-3">
                                    <h6 class="fw-bold">Shipping Address</h6>
                                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                <hr>
                                <div class="mt-3">
                                    <h6 class="fw-bold">Update Status</h6>
                                    <form action="" method="POST" class="row g-2">
                                        <div class="col-md-6">
                                            <select class="form-select" name="status" required>
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                Update Status
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Product</th>
                                                <th scope="col">Size</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col" class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($order_items)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">No items found for this order.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($order_items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['product_image'])): ?>
                                                                    <img src="uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" class="product-img me-3" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                                <?php else: ?>
                                                                    <div class="product-img me-3 bg-light d-flex align-items-center justify-content-center">
                                                                        <i class="fas fa-image text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($item['product_id']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                        <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                                <td class="text-end">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Shipping</td>
                                                <td class="text-end">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Total</td>
                                                <td class="text-end fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Column -->
                    <div class="col-lg-4">
                        <!-- Status Timeline Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Order Status Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-badge" style="background-color: <?php echo $order['status'] == 'pending' || $order['status'] == 'processing' || $order['status'] == 'shipped' || $order['status'] == 'delivered' ? '#28a745' : '#6c757d'; ?>">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <span class="timeline-date"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                                        <h6>Order Placed</h6>
                                        <p class="text-muted">Order has been created and is pending processing.</p>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-badge" style="background-color: <?php echo $order['status'] == 'processing' || $order['status'] == 'shipped' || $order['status'] == 'delivered' ? '#28a745' : '#6c757d'; ?>">
                                            <i class="fas <?php echo $order['status'] == 'processing' || $order['status'] == 'shipped' || $order['status'] == 'delivered' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                        </div>
                                        <span class="timeline-date"><?php echo $order['status'] == 'processing' || $order['status'] == 'shipped' || $order['status'] == 'delivered' ? date('F j, Y, g:i a', strtotime($order['updated_at'] ?? $order['created_at'])) : 'Pending'; ?></span>
                                        <h6>Processing</h6>
                                        <p class="text-muted">Order is being prepared and packed for shipping.</p>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-badge" style="background-color: <?php echo $order['status'] == 'shipped' || $order['status'] == 'delivered' ? '#28a745' : '#6c757d'; ?>">
                                            <i class="fas <?php echo $order['status'] == 'shipped' || $order['status'] == 'delivered' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                        </div>
                                        <span class="timeline-date"><?php echo $order['status'] == 'shipped' || $order['status'] == 'delivered' ? date('F j, Y, g:i a', strtotime($order['updated_at'] ?? $order['created_at'])) : 'Pending'; ?></span>
                                        <h6>Shipped</h6>
                                        <p class="text-muted">Order has been shipped and is on its way.</p>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-badge" style="background-color: <?php echo $order['status'] == 'delivered' ? '#28a745' : '#6c757d'; ?>">
                                            <i class="fas <?php echo $order['status'] == 'delivered' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                        </div>
                                        <span class="timeline-date"><?php echo $order['status'] == 'delivered' ? date('F j, Y, g:i a', strtotime($order['updated_at'] ?? $order['created_at'])) : 'Pending'; ?></span>
                                        <h6>Delivered</h6>
                                        <p class="text-muted">Order has been delivered to the customer.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Summary Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-summary">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Order ID:</span>
                                        <span class="fw-bold">#<?php echo htmlspecialchars($order['id']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Date:</span>
                                        <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Status:</span>
                                        <span class="badge badge-<?php echo htmlspecialchars($order['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Customer:</span>
                                        <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Items:</span>
                                        <span><?php echo count($order_items); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Payment Method:</span>
                                        <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="mailto:<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>" class="btn btn-outline-primary d-block mb-2">
                                        <i class="fas fa-envelope me-1"></i> Email Customer
                                    </a>
                                    <?php if ($order['status'] !== 'cancelled'): ?>
                                        <button type="button" class="btn btn-outline-danger d-block w-100" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                                            <i class="fas fa-ban me-1"></i> Cancel Order
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order #<?php echo $order_id; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
                        <input type="hidden" name="status" value="cancelled">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-danger">Cancel Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>