<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to manage your wishlist'
    ]);
    exit();
}

// Database connection
require_once '../config/database.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$productId = (int)$data['product_id'];

// Check if the product exists
$productCheck = $conn->prepare("SELECT id FROM products WHERE id = ?");
if ($productCheck === false) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit();
}
$productCheck->bind_param("i", $productId);
$productCheck->execute();
$productResult = $productCheck->get_result();

if ($productResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Product not found'
    ]);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if user has a default wishlist, create one if not
    $wishlistCheck = $conn->prepare("SELECT id FROM wishlists WHERE user_id = ? LIMIT 1");
    if ($wishlistCheck === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    $wishlistCheck->bind_param("i", $userId);
    $wishlistCheck->execute();
    $wishlistResult = $wishlistCheck->get_result();
    
    if ($wishlistResult->num_rows === 0) {
        // Create a default wishlist for the user
        $createWishlist = $conn->prepare("INSERT INTO wishlists (user_id, name) VALUES (?, 'My Wishlist')");
        if ($createWishlist === false) {
            throw new Exception("Database error: " . $conn->error);
        }
        $createWishlist->bind_param("i", $userId);
        $createWishlist->execute();
        $wishlistId = $conn->insert_id;
    } else {
        $wishlist = $wishlistResult->fetch_assoc();
        $wishlistId = $wishlist['id'];
    }
    
    // Check if the product is already in the wishlist
    $checkItem = $conn->prepare("SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
    if ($checkItem === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    $checkItem->bind_param("ii", $wishlistId, $productId);
    $checkItem->execute();
    $itemResult = $checkItem->get_result();
    
    $action = '';
    if ($itemResult->num_rows > 0) {
        // Remove item from wishlist
        $deleteItem = $conn->prepare("DELETE FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
        if ($deleteItem === false) {
            throw new Exception("Database error: " . $conn->error);
        }
        $deleteItem->bind_param("ii", $wishlistId, $productId);
        $deleteItem->execute();
        $action = 'removed';
    } else {
        // Add item to wishlist
        $addItem = $conn->prepare("INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)");
        if ($addItem === false) {
            throw new Exception("Database error: " . $conn->error);
        }
        $addItem->bind_param("ii", $wishlistId, $productId);
        $addItem->execute();
        $action = 'added';
    }
    
    // Get updated wishlist count
    $countQuery = $conn->prepare("SELECT COUNT(*) as wishlist_count FROM wishlist_items wi 
                                  INNER JOIN wishlists w ON wi.wishlist_id = w.id 
                                  WHERE w.user_id = ?");
    if ($countQuery === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    $countQuery->bind_param("i", $userId);
    $countQuery->execute();
    $countResult = $countQuery->get_result();
    $countData = $countResult->fetch_assoc();
    $wishlistCount = $countData['wishlist_count'];
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $action === 'added' ? 'Product added to favorites!' : 'Product removed from favorites!',
        'wishlist_count' => $wishlistCount,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating your wishlist.',
        'error' => $e->getMessage()
    ]);
}
?>