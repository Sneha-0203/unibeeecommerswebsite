<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to manage your wishlist'
    ]);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if wishlist_item_id is provided
if (!isset($data['wishlist_item_id']) || !is_numeric($data['wishlist_item_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid wishlist item ID'
    ]);
    exit();
}

// Database connection
require_once '../config/database.php';

// Get user ID from session
$userId = $_SESSION['user_id'];
$wishlistItemId = (int)$data['wishlist_item_id'];

// Check if the wishlist item belongs to the user before removing
$checkQuery = "SELECT wi.id FROM wishlist_items wi 
              JOIN wishlists w ON wi.wishlist_id = w.id 
              WHERE wi.id = ? AND w.user_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ii", $wishlistItemId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Wishlist item not found'
    ]);
    exit();
}

// Delete the wishlist item
$deleteQuery = "DELETE FROM wishlist_items WHERE id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $wishlistItemId);
$success = $deleteStmt->execute();

// Get updated wishlist count
$countQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist_items wi 
               JOIN wishlists w ON wi.wishlist_id = w.id 
               WHERE w.user_id = ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countData = $countResult->fetch_assoc();
$wishlistCount = $countData['wishlist_count'] ?: 0;

// Return response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Item removed from wishlist' : 'Failed to remove item from wishlist',
    'wishlist_count' => $wishlistCount
]);
?>