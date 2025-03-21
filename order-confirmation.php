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

// Retrieve order ID from session
if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit();
}
$orderId = $_SESSION['order_id'];

// Fetch order details
$orderQuery = "SELECT o.id, o.total_amount, o.shipping_address, o.payment_method, o.status, o.created_at
                FROM orders o
                WHERE o.id = ? AND o.user_id = ?";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $orderResult->fetch_assoc();

// Fetch order items
$itemQuery = "SELECT p.name AS product_name, oi.quantity, oi.price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
$itemStmt = $conn->prepare($itemQuery);
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$orderItems = $itemStmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center text-success">Order Confirmed!</h1>
        <p class="text-center">Thank you for your purchase. Your order details are below:</p>
        
        <div class="card p-4">
            <h4>Order Details</h4>
            <p><strong>Order ID:</strong> <?= $order['id'] ?></p>
            <p><strong>Order Date:</strong> <?= $order['created_at'] ?></p>
            <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?></p>
            <p><strong>Order Status:</strong> <?= ucfirst($order['status']) ?></p>
        </div>

        <div class="card mt-3 p-4">
            <h4>Items Ordered</h4>
            <ul class="list-group">
                <?php while ($item = $orderItems->fetch_assoc()) : ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?>
                        <span>&#8377;<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="text-end mt-4">
            <h4>Total Amount: &#8377;<?= number_format($order['total_amount'], 2) ?></h4>
            <a href="index.php" class="btn btn-primary mt-3">Continue Shopping</a>
        </div>
    </div>
</body>
</html>
