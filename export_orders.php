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

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .export-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            margin: 50px auto;
            max-width: 600px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1.1rem;
            margin-top: 20px;
        }
        .export-btn:hover {
            background-color: #218838;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['export_error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['export_error']; ?>
                <?php unset($_SESSION['export_error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="export-container">
            <div class="icon">📊</div>
            <h2>Export Orders</h2>
            <p class="mb-4">Click the button below to export all orders to a CSV file.</p>
            
            <a href="<?php echo $_SERVER['PHP_SELF'] . '?export=true'; ?>" class="btn export-btn">
                Export All Orders to CSV
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include 'includes/footer.php';
?>