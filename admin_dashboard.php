<?php
session_start();

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "shoe_store";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Handle product form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $base_price = (float)$_POST['base_price'];
    $category_id = (int)$_POST['category_id'];
    $image = '';
    
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
            // Store only the filename
            $image = $file_name;
        }
    }
    
    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO products (name, description, base_price, image, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $name, $description, $base_price, $image, $category_id);
    
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        
        // Insert product sizes
        $sizes = $_POST['sizes'];
        $adjustments = $_POST['adjustments'];
        $stocks = $_POST['stocks'];
        
        for ($i = 0; $i < count($sizes); $i++) {
            if (!empty($sizes[$i])) {
                $size = $sizes[$i];
                $adjustment = isset($adjustments[$i]) ? (float)$adjustments[$i] : 0.00;
                $stock = isset($stocks[$i]) ? (int)$stocks[$i] : 0;
                
                $size_stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, price_adjustment, stock) VALUES (?, ?, ?, ?)");
                $size_stmt->bind_param("isdi", $product_id, $size, $adjustment, $stock);
                $size_stmt->execute();
                $size_stmt->close();
            }
        }
        
        $success_message = "Product added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle product deletion if coming from delete action
if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
    $success_message = "Product deleted successfully!";
}

// Fetch categories for dropdown
$categories = [];
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch existing products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  ORDER BY p.created_at DESC 
                  LIMIT ? OFFSET ?";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("ii", $per_page, $offset);
$products_stmt->execute();
$products_result = $products_stmt->get_result();

// Get total number of products for pagination
$count_result = $conn->query("SELECT COUNT(*) as total FROM products");
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $per_page);

// Get order count
$orders_result = $conn->query("SELECT COUNT(*) as total FROM orders");
$orders_row = $orders_result->fetch_assoc();
$order_count = $orders_row['total'];

// Get user count
$users_result = $conn->query("SELECT COUNT(*) as total FROM users");
$users_row = $users_result->fetch_assoc();
$user_count = $users_row['total'];

// Get low stock products (less than 5 items)
$low_stock_query = "SELECT p.name, ps.size, ps.stock 
                    FROM product_sizes ps 
                    JOIN products p ON ps.product_id = p.id 
                    WHERE ps.stock < 5 
                    ORDER BY ps.stock ASC 
                    LIMIT 5";
$low_stock_result = $conn->query($low_stock_query);

// Get recent orders
$recent_orders_query = "SELECT o.id, o.total_amount, o.status, o.created_at, u.name as customer_name 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC 
                        LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UNIBEE</title>
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
        .size-row {
            margin-bottom: 10px;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .size-badge {
            display: inline-block;
            margin: 2px;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        .size-controls {
            margin-top: 10px;
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
                            <a href="admin_dashboard.php" class="nav-link active" aria-current="page">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="admin_products.php" class="nav-link text-white">
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
                <h2 class="mb-4">Admin Dashboard</h2>
                
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
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Products</h5>
                                        <p class="card-text fs-2 mb-0"><?php echo $total_products; ?></p>
                                    </div>
                                    <i class="fas fa-shoe-prints fa-3x opacity-50"></i>
                                </div>
                                <a href="admin_products.php" class="text-white small">View all products <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Categories</h5>
                                        <p class="card-text fs-2 mb-0"><?php echo count($categories); ?></p>
                                    </div>
                                    <i class="fas fa-tags fa-3x opacity-50"></i>
                                </div>
                                <a href="admin_categories.php" class="text-white small">Manage categories <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-dark stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Orders</h5>
                                        <p class="card-text fs-2 mb-0"><?php echo $order_count; ?></p>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                                </div>
                                <a href="admin_orders.php" class="text-dark small">View all orders <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Users</h5>
                                        <p class="card-text fs-2 mb-0"><?php echo $user_count; ?></p>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                    </div>
                                <a href="admin_users.php" class="text-white small">Manage users <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity and Stats -->
                <div class="row mb-4">
                    <!-- Recent Orders -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="admin_orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                                                <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <?php 
                                                            $status_class = '';
                                                            switch ($order['status']) {
                                                                case 'pending':
                                                                    $status_class = 'warning';
                                                                    break;
                                                                case 'processing':
                                                                    $status_class = 'info';
                                                                    break;
                                                                case 'shipped':
                                                                    $status_class = 'primary';
                                                                    break;
                                                                case 'delivered':
                                                                    $status_class = 'success';
                                                                    break;
                                                                case 'cancelled':
                                                                    $status_class = 'danger';
                                                                    break;
                                                                default:
                                                                    $status_class = 'secondary';
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No recent orders found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Products -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Low Stock Products</h5>
                                <a href="admin_inventory.php" class="btn btn-sm btn-outline-primary">View Inventory</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Size</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                                                <?php while ($item = $low_stock_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                                                        <td><?php echo $item['stock']; ?></td>
                                                        <td>
                                                            <?php if ($item['stock'] == 0): ?>
                                                                <span class="badge bg-danger">Out of Stock</span>
                                                            <?php elseif ($item['stock'] < 3): ?>
                                                                <span class="badge bg-warning text-dark">Critical</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Low</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No low stock products found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add Product Form -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Add New Product</h5>
                        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addProductForm" aria-expanded="false" aria-controls="addProductForm">
                            <i class="fas fa-plus me-1"></i> Toggle Form
                        </button>
                    </div>
                    <div class="collapse" id="addProductForm">
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data" id="productForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="base_price" class="form-label">Base Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="base_price" name="base_price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="image" class="form-label">Product Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                    </div>
                                </div>
                                
                                <div class="card mt-4 mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Product Sizes</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> Add different sizes, price adjustments, and stock quantities for this product.
                                        </div>
                                        
                                        <div id="sizes_container">
                                            <!-- Initial size row -->
                                            <div class="row size-row mb-3">
                                                <div class="col-md-4 mb-2">
                                                    <label class="form-label">Size</label>
                                                    <input type="text" class="form-control" name="sizes[]" placeholder="Size (e.g. US 8, EU 41)" required>
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <label class="form-label">Price Adjustment</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" class="form-control" name="adjustments[]" placeholder="Additional price" step="0.01" value="0.00">
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <label class="form-label">Stock</label>
                                                    <input type="number" class="form-control" name="stocks[]" placeholder="Quantity available" min="0" value="0">
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end mb-2">
                                                    <button type="button" class="btn btn-outline-danger remove-size">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="size-controls">
                                            <button type="button" class="btn btn-outline-success" id="add_size">
                                                <i class="fas fa-plus me-1"></i> Add Another Size
                                            </button>
                                        </div>
                                        
                                        <!-- Preview of added sizes -->
                                        <div class="mt-3">
                                            <p><strong>Sizes added:</strong></p>
                                            <div id="size_preview" class="d-flex flex-wrap">
                                                <!-- Preview badges will be added here via JavaScript -->
                                                <span class="text-muted">No sizes added yet</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" name="add_product" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Product
                                    </button>
                                    <button type="reset" class="btn btn-secondary ms-2">
                                        <i class="fas fa-undo me-1"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Product List -->
                <div class="card shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Products</h5>
                        <a href="admin_products.php" class="btn btn-sm btn-outline-primary">View All Products</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Base Price</th>
                                        <th>Sizes Available</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products_result->num_rows > 0): ?>
                                        <?php while ($product = $products_result->fetch_assoc()): 
                                            // Get sizes for this product
                                            $sizes_query = "SELECT size FROM product_sizes WHERE product_id = ? LIMIT 5";
                                            $sizes_stmt = $conn->prepare($sizes_query);
                                            $sizes_stmt->bind_param("i", $product['id']);
                                            $sizes_stmt->execute();
                                            $sizes_result = $sizes_stmt->get_result();
                                            $sizes = [];
                                            while ($size_row = $sizes_result->fetch_assoc()) {
                                                $sizes[] = $size_row['size'];
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $product['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" class="img-thumbnail">
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No Image</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                                <td>$<?php echo number_format($product['base_price'], 2); ?></td>
                                                <td>
                                                    <?php if (count($sizes) > 0): ?>
                                                        <?php foreach ($sizes as $size): ?>
                                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($size); ?></span>
                                                        <?php endforeach; ?>
                                                        <?php 
                                                            // Check if there are more sizes
                                                            $more_sizes_query = "SELECT COUNT(*) as count FROM product_sizes WHERE product_id = ?";
                                                            $more_sizes_stmt = $conn->prepare($more_sizes_query);
                                                            $more_sizes_stmt->bind_param("i", $product['id']);
                                                            $more_sizes_stmt->execute();
                                                            $more_sizes_result = $more_sizes_stmt->get_result();
                                                            $more_sizes_row = $more_sizes_result->fetch_assoc();
                                                            $total_sizes = $more_sizes_row['count'];
                                                            if ($total_sizes > count($sizes)) {
                                                                echo '<span class="badge bg-secondary">+' . ($total_sizes - count($sizes)) . ' more</span>';
                                                            }
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">No sizes</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-danger delete-product" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Product pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Product Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the product "<span id="delete-product-name"></span>"?
                    <p class="text-danger mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Delete Product</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add and remove size inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Add Size button
            document.getElementById('add_size').addEventListener('click', function() {
                const container = document.getElementById('sizes_container');
                const newRow = document.createElement('div');
                newRow.className = 'row size-row mb-3';
                newRow.innerHTML = `
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Size</label>
                        <input type="text" class="form-control" name="sizes[]" placeholder="Size (e.g. US 8, EU 41)" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Price Adjustment</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="adjustments[]" placeholder="Additional price" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" name="stocks[]" placeholder="Quantity available" min="0" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end mb-2">
                        <button type="button" class="btn btn-outline-danger remove-size">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add event listener to the new remove button
                const removeButtons = document.querySelectorAll('.remove-size');
                removeButtons.forEach(button => {
                    button.addEventListener('click', removeSize);
                });
                
                // Update preview
                updateSizePreview();
            });
            
            // Add event listeners to initial Remove buttons
            const removeButtons = document.querySelectorAll('.remove-size');
            removeButtons.forEach(button => {
                button.addEventListener('click', removeSize);
            });
            
            // Function to remove size row
            function removeSize() {
                if (document.querySelectorAll('.size-row').length > 1) {
                    this.closest('.size-row').remove();
                    updateSizePreview();
                } else {
                    alert('You must have at least one size option.');
                }
            }
            
            // Function to update size preview
            function updateSizePreview() {
                const preview = document.getElementById('size_preview');
                const sizeInputs = document.querySelectorAll('input[name="sizes[]"]');
                
                // Clear previous preview
                preview.innerHTML = '';
                
                if (sizeInputs.length > 0) {
                    let hasSizes = false;
                    
                    sizeInputs.forEach((input, index) => {
                        if (input.value) {
                            hasSizes = true;
                            const badge = document.createElement('span');
                            badge.className = 'size-badge me-2';
                            badge.textContent = input.value;
                            preview.appendChild(badge);
                        }
                    });
                    
                    if (!hasSizes) {
                        preview.innerHTML = '<span class="text-muted">No sizes added yet</span>';
                    }
                } else {
                    preview.innerHTML = '<span class="text-muted">No sizes added yet</span>';
                }
            }
            
            // Update preview when sizes change
            document.getElementById('sizes_container').addEventListener('input', function(e) {
                if (e.target && e.target.name === 'sizes[]') {
                    updateSizePreview();
                }
            });
            
            // Initial size preview update
            updateSizePreview();
            
            // Handle delete product button
            const deleteButtons = document.querySelectorAll('.delete-product');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productName = this.getAttribute('data-name');
                    
                    document.getElementById('delete-product-name').textContent = productName;
                    document.getElementById('confirm-delete').href = 'delete_product.php?id=' + productId;
                    
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>