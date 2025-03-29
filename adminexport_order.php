<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

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
          o.total_amount, o.status, o.payment_method, o.shipping_address, o.billing_address, 
          o.created_at, o.updated_at, o.notes
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

// Set appropriate filename with date stamp for uniqueness
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
    'Billing Address',
    'Products',
    'Notes'
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
        $order['billing_address'],
        $products_info,
        $order['notes']
    ];
    
    // Write the row to the CSV file
    fputcsv($output, $row);
}

// Close the file pointer
fclose($output);
exit();
?>