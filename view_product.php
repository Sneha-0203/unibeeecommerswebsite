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

$product_id = intval($_GET['id']);

// Fetch product details
$product_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ?";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();


if ($product_result->num_rows === 0) {
    // Product not found
    header('Location: admin_products.php');
    exit();
}

$product = $product_result->fetch_assoc();

// Fetch product sizes
$sizes_query = "SELECT size, stock FROM product_sizes WHERE product_id = ? ORDER BY size";
$sizes_stmt = $conn->prepare($sizes_query);
$sizes_stmt->bind_param("i", $product_id);
$sizes_stmt->execute();
$sizes_result = $sizes_stmt->get_result();

$sizes = [];
while ($size = $sizes_result->fetch_assoc()) {
    $sizes[] = $size;
}

// Fetch additional product images
$images_query = "SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY display_order";
$images_stmt = $conn->prepare($images_query);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$images = [];
while ($image = $images_result->fetch_assoc()) {
    $images[] = $image;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - UNIBEE Admin</title>
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
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .product-main-image {
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
        }
        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .product-thumbnail.active {
            border-color: #0d6efd;
        }
        .product-thumbnail:hover {
            transform: scale(1.05);
        }
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        .product-info-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        .product-info-item:last-child {
            border-bottom: none;
        }
        .size-stock-badge {
            font-size: 0.85rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .page-header {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }
        .description-box {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 1rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4 page-header">
                    <h2><i class="fas fa-eye me-2 text-secondary"></i>View Product</h2>
                    <div>
                        <a href="admin_products.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Products
                        </a>
                        <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Product
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Product Images -->
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-main-image" id="mainProductImage">
                                    <?php else: ?>
                                        <div class="alert alert-info">No main image available</div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($images) || !empty($product['image'])): ?>
                                    <div class="d-flex flex-wrap justify-content-center">
                                        <?php if (!empty($product['image'])): ?>
                                            <div class="m-1">
                                                <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                    alt="Main Image" class="product-thumbnail active" 
                                                    onclick="changeMainImage(this, 'uploads/products/<?php echo htmlspecialchars($product['image']); ?>')">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php foreach ($images as $image): ?>
                                            <div class="m-1">
                                                <img src="uploads/product_images/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                    alt="Product Image" class="product-thumbnail" 
                                                    onclick="changeMainImage(this, 'uploads/product_images/<?php echo htmlspecialchars($image['image_path']); ?>')">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h4 class="mb-0"><i class="fas fa-info-circle me-2 text-secondary"></i>Product Details</h4>
                            </div>
                            <div class="card-body">
                                <div class="product-info-item">
                                    <div class="d-flex justify-content-between">
                                        <h3 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <span class="badge bg-success">ID: <?php echo $product['id']; ?></span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-info text-dark me-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <?php if ($product['featured']): ?>
                                            <span class="badge bg-warning text-dark">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-info-item">
                                    <h5>Pricing</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="text-muted">Base Price:</span>
                                                <div class="fw-bold fs-4 text-success">$<?php echo number_format($product['base_price'], 2); ?></div>
                                            </div>
                                        </div>
                                        <?php if (isset($product['sale_price']) && $product['sale_price'] > 0): ?>
                                            <div class="col-md-4">
                                                <div class="mb-2">
                                                    <span class="text-muted">Sale Price:</span>
                                                    <div class="fw-bold fs-4 text-danger">$<?php echo number_format($product['sale_price'], 2); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-2">
                                                    <span class="text-muted">Discount:</span>
                                                    <div class="fw-bold fs-5">
                                                        <?php 
                                                        $discount = round(($product['base_price'] - $product['sale_price']) / $product['base_price'] * 100);
                                                        echo $discount . '%';
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="product-info-item">
                                    <h5>Inventory</h5>
                                    <div class="mb-3">
                                        <h6>Available Sizes & Stock:</h6>
                                        <div>
                                            <?php if (!empty($sizes)): ?>
                                                <?php foreach ($sizes as $size): ?>
                                                    <span class="badge bg-light text-dark border size-stock-badge">
                                                        Size <?php echo htmlspecialchars($size['size']); ?>: 
                                                        <span class="<?php echo $size['stock'] > 10 ? 'text-success' : ($size['stock'] > 0 ? 'text-warning' : 'text-danger'); ?>">
                                                            <?php echo $size['stock']; ?> in stock
                                                        </span>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No sizes available</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="text-muted">Total Stock:</span>
                                                <div class="fw-bold">
                                                    <?php
                                                    $total_stock = 0;
                                                    foreach ($sizes as $size) {
                                                        $total_stock += $size['stock'];
                                                    }
                                                    echo $total_stock;
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <span class="text-muted">SKU:</span>
                                                <div class="fw-bold"><?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : 'N/A'; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($product['description'])): ?>
                                <div class="product-info-item">
                                    <h5>Description</h5>
                                    <div class="description-box">
                                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="product-info-item">
                                    <h5>Additional Details</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="text-muted">Created At:</span>
                                                <div><?php echo date('F d, Y h:i A', strtotime($product['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="text-muted">Last Updated:</span>
                                                <div>
                                                    <?php echo !empty($product['updated_at']) ? date('F d, Y h:i A', strtotime($product['updated_at'])) : 'Never'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions Button Group -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Product
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $product_id; ?>" class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash me-1"></i> Delete Product
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Products Section - Placeholder -->
                <?php
                // Fetch related products (same category)
                $related_query = "SELECT id, name, image, base_price, sale_price 
                                 FROM products 
                                 WHERE category_id = ? AND id != ? 
                                 LIMIT 4";
                $related_stmt = $conn->prepare($related_query);
                $related_stmt->bind_param("ii", $product['category_id'], $product_id);
                $related_stmt->execute();
                $related_result = $related_stmt->get_result();
                
                if ($related_result->num_rows > 0):
                ?>
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-link me-2 text-secondary"></i>Related Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while($related = $related_result->fetch_assoc()): ?>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="card h-100">
                                        <div class="text-center p-2">
                                            <?php if (!empty($related['image'])): ?>
                                                <img src="uploads/products/<?php echo htmlspecialchars($related['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($related['name']); ?>" class="img-thumbnail" style="height: 120px; object-fit: contain;">
                                            <?php else: ?>
                                                <div class="bg-light p-4 text-center">
                                                    <i class="fas fa-image fa-2x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body pb-2">
                                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($related['name']); ?></h6>
                                            <?php if (isset($related['sale_price']) && $related['sale_price'] > 0): ?>
                                                <div>
                                                    <span class="text-decoration-line-through text-muted">$<?php echo number_format($related['base_price'], 2); ?></span>
                                                    <span class="text-danger fw-bold">$<?php echo number_format($related['sale_price'], 2); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="fw-bold">$<?php echo number_format($related['base_price'], 2); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white border-top-0 pt-0">
                                            <a href="view_product.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to change main product image
        function changeMainImage(thumbnailElement, imageSrc) {
            // Update main image
            document.getElementById('mainProductImage').src = imageSrc;
            
            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.product-thumbnail');
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnailElement.classList.add('active');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltip initialization
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>