<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return JSON error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to add items to wishlist']);
        exit;
    }
    
    // Redirect to login page for regular requests
    header('Location: index.php?login=required');
    exit;
}

// Check if product ID was provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    // Return JSON error for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No product specified']);
        exit;
    }
    
    // Redirect back for regular requests
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'user_products.php');
    exit;
}

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

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

// Check if item is already in wishlist
$check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Item already in wishlist
    
    // For AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'info', 'message' => 'This item is already in your wishlist']);
        exit;
    }
    
    // For regular requests
    $_SESSION['message'] = "This item is already in your wishlist.";
    $_SESSION['message_type'] = "info";
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'user_products.php');
    exit;
}

// Add item to wishlist
$stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, date_added) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    // Item added successfully
    
    // For AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Item added to wishlist']);
        exit;
    }
    
    // For regular requests
    $_SESSION['message'] = "Item added to wishlist successfully.";
    $_SESSION['message_type'] = "success";
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'user_products.php');
    exit;
} else {
    // Error adding item
    
    // For AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to add item to wishlist']);
        exit;
    }
    
    // For regular requests
    $_SESSION['message'] = "Failed to add item to wishlist.";
    $_SESSION['message_type'] = "error";
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'user_products.php');
    exit;
}