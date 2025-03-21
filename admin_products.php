<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
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

// Add search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = " WHERE p.name LIKE ? ";
    $params[] = "%$search%";
    $types .= 's';
}

if ($category_filter > 0) {
    if (empty($where_clause)) {
        $where_clause = " WHERE p.category_id = ? ";
    } else {
        $where_clause .= " AND p.category_id = ? ";
    }
    $params[] = $category_filter;
    $types .= 'i';
}

$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  $where_clause
                  ORDER BY p.created_at DESC 
                  LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$products_stmt = $conn->prepare($products_query);

// FIX: Only bind parameters if there are parameters to bind and types is not empty
if (!empty($params) && !empty($types)) {
    $products_stmt->bind_param($types, ...$params);
}

$products_stmt->execute();
$products_result = $products_stmt->get_result();

// Get total number of products for pagination
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    // Remove the last two parameters (limit and offset) for the count query
    $count_params = array_slice($params, 0, -2);
    $count_types = substr($types, 0, -2);
    
    // FIX: Only bind parameters if there are parameters to bind and count_types is not empty
    if (!empty($count_params) && !empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Shoe Store Admin</title>
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
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .btn-group .btn {
            border-radius: 4px;
            margin: 0 2px;
        }
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        .img-thumbnail {
            object-fit: cover;
            border-radius: 4px;
        }
        .pagination .page-link {
            color: #212529;
            border-color: #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: #212529;
            border-color: #212529;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
        }
        .alert {
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-success {
            border-left-color: #198754;
        }
        .page-header {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
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
                <div class="d-flex justify-content-between align-items-center mb-4 page-header">
                    <h2><i class="fas fa-shoe-prints me-2 text-secondary"></i>Products Management</h2>
                    <a href="admin_add_product.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Product
                    </a>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                                    <input type="text" class="form-control" placeholder="Search products..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-filter text-secondary"></i></span>
                                    <select class="form-select" name="category" onchange="this.form.submit()">
                                        <option value="0">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <?php if (!empty($search) || $category_filter > 0): ?>
                                    <a href="admin_products.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-1"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Product List -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2 text-secondary"></i>Product List</h5>
                            <span class="badge bg-secondary"><?php echo $total_products; ?> Products</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Base Price</th>
                                        <th>Sizes</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products_result->num_rows > 0): ?>
                                        <?php while ($product = $products_result->fetch_assoc()): ?>
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
                                                <td>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">$<?php echo number_format($product['base_price'], 2); ?></span>
                                                    </td>
                                                <td>
                                                    <?php
                                                    // Get sizes for this product
                                                    $sizes_query = "SELECT size FROM product_sizes WHERE product_id = ? LIMIT 5";
                                                    $sizes_stmt = $conn->prepare($sizes_query);
                                                    $sizes_stmt->bind_param("i", $product['id']);
                                                    $sizes_stmt->execute();
                                                    $sizes_result = $sizes_stmt->get_result();
                                                    
                                                    $sizes = [];
                                                    while ($size = $sizes_result->fetch_assoc()) {
                                                        $sizes[] = $size['size'];
                                                    }
                                                    
                                                    if (!empty($sizes)) {
                                                        echo '<span class="text-secondary">'.implode(", ", $sizes).'</span>';
                                                        
                                                        // Show a "more" indicator if there are more than 5 sizes
                                                        $count_query = "SELECT COUNT(*) as total FROM product_sizes WHERE product_id = ?";
                                                        $count_stmt = $conn->prepare($count_query);
                                                        $count_stmt->bind_param("i", $product['id']);
                                                        $count_stmt->execute();
                                                        $count_result = $count_stmt->get_result();
                                                        $count_row = $count_result->fetch_assoc();
                                                        
                                                        if ($count_row['total'] > 5) {
                                                            echo " <span class='badge bg-info text-dark'>+" . ($count_row['total'] - 5) . " more</span>";
                                                        }
                                                    } else {
                                                        echo "<span class='badge bg-warning text-dark'>No sizes</span>";
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-search fa-2x mb-3 text-secondary"></i>
                                                    <p>No products found</p>
                                                    <?php if (!empty($search) || $category_filter > 0): ?>
                                                        <a href="admin_products.php" class="btn btn-sm btn-outline-secondary mt-2">Clear search filters</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    // For large number of pages, show limited page numbers
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : '') . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php
                                    endfor;
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : '') . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                        <?php if ($products_result->num_rows > 0): ?>
                            <div class="text-center text-muted small mt-3">
                                Showing <?php echo min(($page - 1) * $per_page + 1, $total_products); ?> to <?php echo min($page * $per_page, $total_products); ?> of <?php echo $total_products; ?> products
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
            
            // Add tooltip initialization
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '';
                });
            });
        });
    </script>
</body>
</html>