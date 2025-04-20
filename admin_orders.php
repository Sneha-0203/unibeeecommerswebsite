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

// Function to safely export orders
function export_orders() {
    global $conn;
    
    try {
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
        $search_term = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Build the query
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($status_filter)) {
            $where_conditions[] = "o.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(o.created_at) >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(o.created_at) <= ?";
            $params[] = $date_to;
            $types .= 's';
        }
        
        if (!empty($search_term)) {
            $where_conditions[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
            $search_param = "%$search_term%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'sss';
        }
        
        $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get orders for export
        $query = "SELECT o.id, o.user_id, u.name as customer_name, u.email as customer_email, 
                  o.total_amount, o.status, o.payment_method, o.shipping_address, 
                  o.created_at
                  FROM orders o 
                  LEFT JOIN users u ON o.user_id = u.id
                  $where_clause
                  ORDER BY o.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // For each order, get the order items
        foreach ($orders as $key => $order) {
            $items_query = "SELECT oi.*, p.name as product_name 
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param('i', $order['id']);
            $items_stmt->execute();
            $orders[$key]['items'] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Set appropriate filename with date stamp
        $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create a file pointer
        $output = fopen('php://output', 'w');
        
        // Set column headers for the CSV file
        $headers = [
            'Order ID', 
            'Customer Name', 
            'Customer Email', 
            'Total Amount ($)', 
            'Status', 
            'Payment Method', 
            'Order Date', 
            'Shipping Address',
            'Products'
        ];
        
        // Write the headers to the CSV file
        fputcsv($output, $headers);
        
        // Write each order as a row in the CSV
        foreach ($orders as $order) {
            // Format products information
            $products_info = '';
            foreach ($order['items'] as $item) {
                $products_info .= $item['product_name'] . ' (Size: ' . $item['size'] . ', Qty: ' . $item['quantity'] . ', Price: $' . $item['price'] . '), ';
            }
            $products_info = rtrim($products_info, ', ');
            
            // Create the row data
            $row = [
                '#' . $order['id'],
                $order['customer_name'],
                $order['customer_email'],
                number_format($order['total_amount'], 2),
                ucfirst($order['status']),
                $order['payment_method'],
                date('Y-m-d H:i:s', strtotime($order['created_at'])),
                $order['shipping_address'],
                $products_info
            ];
            
            // Write the row to the CSV file
            fputcsv($output, $row);
        }
        
        // Close the file pointer
        fclose($output);
        return true;
    } catch (Exception $e) {
        // Log the error but don't display it
        error_log('Export error: ' . $e->getMessage());
        return false;
    }
}

// Check if this is an export request
if (isset($_GET['export']) && $_GET['export'] == 'true') {
    if (export_orders()) {
        exit(); // Exit after successful export
    } else {
        $_SESSION['export_error'] = "There was an error exporting the data. Please try again or contact support.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($search_term)) {
    $where_conditions[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Get total number of records
$count_query = "SELECT COUNT(*) as total FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id
                $where_clause";

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get orders for current page
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id
          $where_clause
          ORDER BY o.created_at DESC 
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
$bind_types = $types . 'ii';
$params[] = $offset;
$params[] = $records_per_page;
$stmt->bind_param($bind_types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Order #$order_id status updated to $new_status successfully.";
        // Refresh the page to show updated data
        header("Location: admin_orders.php?page=$page" . 
               (!empty($status_filter) ? "&status=$status_filter" : "") . 
               (!empty($date_from) ? "&date_from=$date_from" : "") . 
               (!empty($date_to) ? "&date_to=$date_to" : "") . 
               (!empty($search_term) ? "&search=$search_term" : "") . 
               "&success=".urlencode($success_message));
        exit();
    } else {
        $error_message = "Error updating order status: " . $update_stmt->error;
    }
}

// Check for success message in URL
$success_message = isset($_GET['success']) ? $_GET['success'] : '';

// Check for export error message
$export_error = isset($_SESSION['export_error']) ? $_SESSION['export_error'] : '';
if (isset($_SESSION['export_error'])) {
    unset($_SESSION['export_error']);
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        .order-items-table {
            font-size: 0.9rem;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .export-btn:hover {
            background-color: #218838;
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
                        <span class="fs-4">UNIBEE Admin</span>
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
                            <a href="admin_products.php" class="nav-link" aria-current="page">
                                <i class="fas fa-shoe-prints me-2"></i>
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="admin_categories.php" class="nav-link  text-white">
                                <i class="fas fa-tags me-2"></i>
                                Categories
                            </a>
                        </li>
                        <li>
                            <a href="admin_orders.php" class="nav-link active text-white">
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
                            <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : htmlspecialchars($_SESSION['username']); ?></strong>
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
                    <h2><i class="fas fa-shopping-cart me-2"></i> Orders Management</h2>
                    <a href="?export=true<?php echo !empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : ''; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>" class="btn export-btn">
                        <i class="fas fa-file-export me-1"></i> Export Orders
                    </a>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($export_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($export_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filter Orders</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="text" class="form-control datepicker" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="text" class="form-control datepicker" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Order ID, Customer...">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <a href="admin_orders.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo me-1"></i> Reset Filters
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                </button>
                            </div>
                            <!-- Keep the page parameter if set -->
                            <?php if (isset($_GET['page'])): ?>
                                <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']); ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order List</h5>
                        <span class="badge bg-secondary"><?php echo $total_records; ?> orders found</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Order ID</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Payment</th>
                                        <th scope="col">Date</th>
                                        <th scope="col" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">No orders found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                                <td>
                                                    <?php if (!empty($order['user_name'])): ?>
                                                        <div><?php echo htmlspecialchars($order['user_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">User not found</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo htmlspecialchars($order['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td class="text-end">
                                                <a href="admin_order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-info me-1 btn-view" title="View Order Details">
    <i class="fas fa-eye"></i>
</a>
                                                        
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#changeStatusModal"
                                                            data-order-id="<?php echo $order['id']; ?>"
                                                            data-current-status="<?php echo $order['status']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : ''; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' 
                                            . (!empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : '')
                                            . (!empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : '')
                                            . (!empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : '')
                                            . (!empty($search_term) ? '&search=' . htmlspecialchars($search_term) : '')
                                            . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i 
                                            . (!empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : '')
                                            . (!empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : '')
                                            . (!empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : '')
                                            . (!empty($search_term) ? '&search=' . htmlspecialchars($search_term) : '')
                                            . '">' . $i . '</a></li>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages 
                                            . (!empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : '')
                                            . (!empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : '')
                                            . (!empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : '')
                                            . (!empty($search_term) ? '&search=' . htmlspecialchars($search_term) : '')
                                            . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . htmlspecialchars($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . htmlspecialchars($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . htmlspecialchars($date_to) : ''; ?><?php echo !empty($search_term) ? '&search=' . htmlspecialchars($search_term) : ''; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading order details...</p>
                    </div>
                    <div id="orderDetails" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <span id="customerName"></span></p>
                                <p class="mb-1"><strong>Email:</strong> <span id="customerEmail"></span></p>
                                <p class="mb-1"><strong>Shipping Address:</strong> <span id="shippingAddress"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Order Information</h6>
                                <p class="mb-1"><strong>Order ID:</strong> #<span id="orderId"></span></p>
                                <p class="mb-1"><strong>Date:</strong> <span id="orderDate"></span></p>
                                <p class="mb-1"><strong>Status:</strong> <span id="orderStatus"></span></p>
                                <p class="mb-1"><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
                                <p class="mb-1"><strong>Total Amount:</strong> $<span id="totalAmount"></span></p>
                            </div>
                        </div>
                        <h6 class="fw-bold">Order Items</h6>
                        <div class="table-responsive order-items-table">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Size</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="orderItemsBody">
                                    <!-- Order items will be inserted here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn">
                        <i class="fas fa-print me-1"></i> Print Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });

        // Handle order detail modal
        const orderDetailModal = document.getElementById('orderDetailModal');
        if (orderDetailModal) {
            orderDetailModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                
                // Show loading spinner
                document.getElementById('orderDetails').style.display = 'none';
                const spinner = orderDetailModal.querySelector('.spinner-border').parentElement;
                spinner.style.display = 'block';
                
                // Fetch order details via AJAX
                fetch(`get_order_details.php?id=${orderId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Hide spinner
                        spinner.style.display = 'none';
                        document.getElementById('orderDetails').style.display = 'block';
                        
                        // Populate modal with order details
                        document.getElementById('orderId').textContent = data.id;
                        document.getElementById('customerName').textContent = data.customer_name;
                        document.getElementById('customerEmail').textContent = data.customer_email;
                        document.getElementById('shippingAddress').textContent = data.shipping_address;
                        document.getElementById('orderDate').textContent = new Date(data.created_at).toLocaleString();
                        document.getElementById('orderStatus').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        document.getElementById('paymentMethod').textContent = data.payment_method;
                        document.getElementById('totalAmount').textContent = parseFloat(data.total_amount).toFixed(2);
                        
                        // Create order items table rows
                        const orderItemsBody = document.getElementById('orderItemsBody');
                        orderItemsBody.innerHTML = '';
                        
                        data.items.forEach(item => {
                            const row = document.createElement('tr');
                            const subtotal = parseFloat(item.price) * parseInt(item.quantity);
                            
                            row.innerHTML = `
                                <td>${item.product_name}</td>
                                <td>${item.size}</td>
                                <td>$${parseFloat(item.price).toFixed(2)}</td>
                                <td>${item.quantity}</td>
                                <td>$${subtotal.toFixed(2)}</td>
                            `;
                            
                            orderItemsBody.appendChild(row);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching order details:', error);
                        spinner.style.display = 'none';
                        document.getElementById('orderDetails').innerHTML = `
                            <div class="alert alert-danger">
                                There was an error loading the order details. Please try again.
                            </div>
                        `;
                        document.getElementById('orderDetails').style.display = 'block';
                    });
            });
        }

        // Handle change status modal
        const changeStatusModal = document.getElementById('changeStatusModal');
        if (changeStatusModal) {
            changeStatusModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                const currentStatus = button.getAttribute('data-current-status');
                
                document.getElementById('statusOrderId').value = orderId;
                const statusSelect = document.getElementById('new_status');
                
                // Set current status as selected
                for (let i = 0; i < statusSelect.options.length; i++) {
                    if (statusSelect.options[i].value === currentStatus) {
                        statusSelect.options[i].selected = true;
                        break;
                    }
                }
                
                // Update modal title with order ID
                document.getElementById('changeStatusModalLabel').textContent = `Update Status for Order #${orderId}`;
            });
        }

        // Print order function
        document.getElementById('printOrderBtn').addEventListener('click', function() {
            const printContents = document.getElementById('orderDetails').innerHTML;
            const originalContents = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h2 style="text-align: center; margin-bottom: 20px;">Order Details</h2>
                    ${printContents}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        });
    </script>
</body>
</html>