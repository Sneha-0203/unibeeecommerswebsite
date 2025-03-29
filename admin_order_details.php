<?php
// Start session first
session_start();

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "shoe_store";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin is logged in - using the same session variable as your other admin pages
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Get order ID from URL parameter
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo "Invalid order ID";
    exit();
}

// First, let's check the structure of the users table to get the column names
$user_columns_query = "SHOW COLUMNS FROM users";
$user_columns_result = $conn->query($user_columns_query);
$user_columns = [];
while ($column = $user_columns_result->fetch_assoc()) {
    $user_columns[] = $column['Field'];
}

// Prepare the user fields based on actual table structure
$user_fields = [];
if (in_array('name', $user_columns)) {
    $user_fields[] = 'u.name';
} else if (in_array('first_name', $user_columns) && in_array('last_name', $user_columns)) {
    $user_fields[] = 'u.first_name';
    $user_fields[] = 'u.last_name';
} else {
    // Fallback if neither exists
    $user_fields[] = 'u.id as user_id';
}

// Add email and phone if they exist
if (in_array('email', $user_columns)) {
    $user_fields[] = 'u.email';
}
if (in_array('phone', $user_columns)) {
    $user_fields[] = 'u.phone';
}

// Construct the final order query
$user_fields_str = implode(', ', $user_fields);
$order_query = "SELECT o.*, $user_fields_str 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows == 0) {
    echo "Order not found";
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name as product_name, p.image as product_image,
                ps.size as size_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN product_sizes ps ON oi.product_size_id = ps.id
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully";
        // Update local order data
        $order['status'] = $new_status;
    } else {
        $error_message = "Failed to update order status: " . $conn->error;
    }
}

// Function to display customer name based on available fields
function getCustomerName($order) {
    if (isset($order['name'])) {
        return $order['name'];
    } else if (isset($order['first_name']) && isset($order['last_name'])) {
        return $order['first_name'] . ' ' . $order['last_name'];
    } else {
        return "Customer #" . $order['user_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Header styling */
        .admin-header {
            background: linear-gradient(120deg, #2b5876, #4e4376);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-header .logo {
            display: flex;
            align-items: center;
        }

        .admin-header .logo i {
            font-size: 2rem;
            margin-right: 15px;
            color: #f8f9fa;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .header-actions .btn {
            margin-left: 10px;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .header-actions .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .admin-header .breadcrumb {
            background-color: transparent;
            padding: 10px 0 0 0;
            margin: 0;
        }

        .admin-header .breadcrumb-item {
            color: rgba(255, 255, 255, 0.7);
        }

        .admin-header .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .admin-header .breadcrumb-item a:hover {
            color: white;
            text-decoration: none;
        }

        .admin-header .breadcrumb-item.active {
            color: white;
        }

        .admin-header .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Main styling */
        body {
            background-color: #f8f9fa;
            color: #495057;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .container {
            max-width: 1200px;
            padding: 20px;
        }

        /* Card styling */
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(to right, #3a7bd5, #00d2ff);
            color: white;
            border-bottom: none;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }

        /* Page title */
        h2 {
            color: #343a40;
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h2:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: linear-gradient(to right, #3a7bd5, #00d2ff);
        }

        /* Order status colors - enhanced */
        .order-status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 15px;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-shipped {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Table styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table th, .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.03);
        }

        /* Product image */
        .product-image {
            max-width: 80px;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .product-image:hover {
            transform: scale(1.1);
        }

        /* Form elements */
        .form-select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }

        .form-select:focus {
            border-color: #3a7bd5;
            box-shadow: 0 0 0 0.25rem rgba(58, 123, 213, 0.25);
        }

        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, #3a7bd5, #00d2ff);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #2d62aa, #00b8e6);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 15px 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Animation for status updates */
        @keyframes highlight {
            0% { background-color: rgba(255, 255, 0, 0.3); }
            100% { background-color: transparent; }
        }

        .highlight {
            animation: highlight 2s ease-out;
        }

        /* Footer spacing */
        .mt-3.mb-5 {
            margin-top: 2rem !important;
            margin-bottom: 3rem !important;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .admin-header h1 {
                font-size: 1.4rem;
            }
            
            .admin-header .logo i {
                font-size: 1.6rem;
                margin-right: 10px;
            }
            
            .header-actions .btn {
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            .card-header h5 {
                font-size: 1rem;
            }
            
            .table th {
                font-size: 0.75rem;
            }
            
            .product-image {
                max-width: 60px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-shoe-prints"></i>
                <h1>Shoe Store Admin</h1>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn btn-outline-light">Dashboard</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="admin_orders.php">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $order_id; ?></li>
                </ol>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-12">                
                <h2>Order #<?php echo $order_id; ?> Details</h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Order Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </p>
                                <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                
                                <form method="POST" class="mt-3">
                                    <div class="form-group">
                                        <label for="status"><strong>Update Status:</strong></label>
                                        <select name="status" id="status" class="form-select">
                                            <option value="pending" <?php if ($order['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="processing" <?php if ($order['status'] == 'processing') echo 'selected'; ?>>Processing</option>
                                            <option value="shipped" <?php if ($order['status'] == 'shipped') echo 'selected'; ?>>Shipped</option>
                                            <option value="delivered" <?php if ($order['status'] == 'delivered') echo 'selected'; ?>>Delivered</option>
                                            <option value="cancelled" <?php if ($order['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary mt-2">Update Status</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars(getCustomerName($order)); ?></p>
                                
                                <?php if (isset($order['email'])): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (isset($order['phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                <?php endif; ?>
                                
                                <p><strong>Shipping Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Image</th>
                                        <th>Size</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td>
                                                <?php if (!empty($item['product_image'])): ?>
                                                    <img src="uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" class="product-image" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['size_name']); ?></td>
                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 mb-5">
                    <a href="admin_orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>         