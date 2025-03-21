<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_products.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Check if the product exists
$check_product = $conn->prepare("SELECT id, name, image FROM products WHERE id = ?");
$check_product->bind_param("i", $product_id);
$check_product->execute();
$result = $check_product->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found!";
    header('Location: admin_products.php');
    exit();
}

$product = $result->fetch_assoc();

// Handle confirmation
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete product sizes
        $delete_sizes = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $delete_sizes->bind_param("i", $product_id);
        $delete_sizes->execute();
        
        // Delete product images (additional images if there's a separate table)
        // If you have a product_images table, uncomment this
        /*
        $delete_images = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $delete_images->bind_param("i", $product_id);
        $delete_images->execute();
        */
        
        // Delete product
        $delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_product->bind_param("i", $product_id);
        $delete_product->execute();
        
        // Delete product image file if exists
        if (!empty($product['image'])) {
            $image_path = 'uploads/products/' . $product['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect with success message
        header('Location: admin_products.php?delete=success');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
        header('Location: admin_products.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product - Shoe Store Admin</title>
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
        .delete-card {
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            border-top: 4px solid #dc3545;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
            border-radius: 4px;
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
                            <a href="admin_products.php" class="nav-link active" aria-current="page">
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
                            <a href="admin_orders.php" class="nav-link text-white">
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
                        <li>
                            <a href="admin_settings.php" class="nav-link text-white">
                                <i class="fas fa-cog me-2"></i>
                                Settings
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
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="admin_products.php">Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Delete Product</li>
                    </ol>
                </nav>

                <div class="delete-card card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Delete Product
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image mb-3">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center product-image mb-3 mx-auto">
                                        <i class="fas fa-image text-secondary fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="text-danger mb-4">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                Are you sure you want to delete this product? This action cannot be undone.
                            </p>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Warning:</strong> Deleting this product will also remove all associated sizes and inventory data.
                            </div>
                        </div>
                        
                        <form method="post" action="">
                            <div class="d-flex justify-content-center gap-3">
                                <a href="admin_products.php" class="btn btn-secondary px-4">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger px-4">
                                    <i class="fas fa-trash-alt me-2"></i>Delete Permanently
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>