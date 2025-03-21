<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: admin_dashboard.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Fetch categories for dropdown
$categories = [];
$categories_result = $conn->query("SELECT id, name FROM categories");
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch product sizes
$sizes = [];
$sizes_stmt = $conn->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
$sizes_stmt->bind_param("i", $product_id);
$sizes_stmt->execute();
$sizes_result = $sizes_stmt->get_result();
while ($row = $sizes_result->fetch_assoc()) {
    $sizes[] = $row;
}
$sizes_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $base_price = (float)$_POST['base_price'];
    $category_id = (int)$_POST['category_id'];
    $image = $product['image']; // Keep existing image by default
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Delete old image if exists
            if (!empty($product['image']) && file_exists($product['image'])) {
                unlink($product['image']);
            }
            $image = $target_file;
        }
    }
    
    // Update product in database
    $update_stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, base_price = ?, image = ?, category_id = ? WHERE id = ?");
    $update_stmt->bind_param("ssdsii", $name, $description, $base_price, $image, $category_id, $product_id);
    
    if ($update_stmt->execute()) {
        // Delete existing sizes
        $delete_stmt = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Insert updated product sizes
        $sizes = $_POST['sizes'];
        $adjustments = $_POST['adjustments'];
        $stocks = $_POST['stocks'];
        $size_ids = $_POST['size_ids'];
        
        for ($i = 0; $i < count($sizes); $i++) {
            if (!empty($sizes[$i])) {
                $size = $sizes[$i];
                $adjustment = isset($adjustments[$i]) ? (float)$adjustments[$i] : 0.00;
                $stock = isset($stocks[$i]) ? (int)$stocks[$i] : 0;
                $size_id = isset($size_ids[$i]) ? (int)$size_ids[$i] : 0;
                
                $size_stmt = $conn->prepare("INSERT INTO product_sizes (id, product_id, size, price_adjustment, stock) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE size = ?, price_adjustment = ?, stock = ?");
                $size_stmt->bind_param("iisdiidi", $size_id, $product_id, $size, $adjustment, $stock, $size, $adjustment, $stock);
                $size_stmt->execute();
                $size_stmt->close();
            }
        }
        
        $success_message = "Product updated successfully!";
        
        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        // Refresh sizes
        $sizes = [];
        $sizes_stmt = $conn->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
        $sizes_stmt->bind_param("i", $product_id);
        $sizes_stmt->execute();
        $sizes_result = $sizes_stmt->get_result();
        while ($row = $sizes_result->fetch_assoc()) {
            $sizes[] = $row;
        }
        $sizes_stmt->close();
    } else {
        $error_message = "Error: " . $update_stmt->error;
    }
    
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            background-color: #212529;
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
        .size-row {
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                height: auto;
            }
            .content {
                margin-left: 0;
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
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="admin_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin_logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-10 content">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Product</h5>
                        <a href="admin_products.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Products
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="base_price" class="form-label">Base Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="base_price" name="base_price" value="<?php echo $product['base_price']; ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Product Image</label>
                                        <?php if (!empty($product['image'])): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="max-height: 150px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <small class="text-muted">Leave empty to keep current image</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Product Sizes</h6>
                                </div>
                                <div class="card-body">
                                    <div id="size-container">
                                        <?php if (empty($sizes)): ?>
                                            <div class="row size-row">
                                                <input type="hidden" name="size_ids[]" value="0">
                                                <div class="col-md-4">
                                                    <input type="text" class="form-control" name="sizes[]" placeholder="Size (e.g., S, M, L, XL, 8, 9, 10)">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" class="form-control" name="adjustments[]" placeholder="Price Adjustment" step="0.01" value="0.00">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" class="form-control" name="stocks[]" placeholder="Stock" min="0" value="0">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-danger remove-size">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($sizes as $size): ?>
                                                <div class="row size-row">
                                                    <input type="hidden" name="size_ids[]" value="<?php echo $size['id']; ?>">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="sizes[]" placeholder="Size (e.g., S, M, L, XL, 8, 9, 10)" value="<?php echo htmlspecialchars($size['size']); ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" class="form-control" name="adjustments[]" placeholder="Price Adjustment" step="0.01" value="<?php echo $size['price_adjustment']; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" name="stocks[]" placeholder="Stock" min="0" value="<?php echo $size['stock']; ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger remove-size">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary" id="add-size">
                                            <i class="fas fa-plus me-1"></i> Add Size
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="update_product" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Update Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add new size
            document.getElementById('add-size').addEventListener('click', function () {
                const container = document.getElementById('size-container');
                const newRow = document.createElement('div');
                newRow.className = 'row size-row';
                newRow.innerHTML = `
                    <input type="hidden" name="size_ids[]" value="0">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="sizes[]" placeholder="Size (e.g., S, M, L, XL, 8, 9, 10)">
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="adjustments[]" placeholder="Price Adjustment" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="stocks[]" placeholder="Stock" min="0" value="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-size">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-size').addEventListener('click', function () {
                    container.removeChild(newRow);
                });
            });
            
            // Remove size
            document.querySelectorAll('.remove-size').forEach(button => {
                button.addEventListener('click', function () {
                    const row = this.closest('.size-row');
                    row.parentNode.removeChild(row);
                });
            });
        });
    </script>
</body>
</html>