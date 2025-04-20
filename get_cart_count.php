<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart count
$count = 0;

// Count items in cart if it exists
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $count += isset($item['quantity']) ? (int)$item['quantity'] : 0;
    }
}

// Set proper JSON content type
header('Content-Type: application/json');

// Return JSON response
echo json_encode(['count' => $count]);
exit;
?>