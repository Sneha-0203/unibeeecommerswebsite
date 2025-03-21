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

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get categories for filter - FIXED: Use DISTINCT to prevent duplicates
$categoriesQuery = "SELECT DISTINCT id, name FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);

// Handle category filter with proper sanitization
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$whereClause = $categoryFilter > 0 ? "WHERE p.category_id = ?" : "";

// Prepare the products query with parameters to prevent SQL injection
$query = "SELECT p.*, c.name as category_name, 
          (SELECT SUM(stock) FROM product_sizes WHERE product_id = p.id) as total_stock
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          $whereClause
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if ($categoryFilter > 0) {
    $stmt->bind_param("i", $categoryFilter);
}
$stmt->execute();
$result = $stmt->get_result();

// Get cart count
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.3s;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .badge-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            border-radius: 20px;
            padding: 6px 12px;
            font-weight: 500;
        }
        /* Improved dropdown styling */
        .filter-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
            background-color: #fff;
        }
        .filter-btn {
            border-radius: 6px;
            padding: 8px 16px;
        }
        /* Category badge styling */
        .category-badge {
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        /* Footer link hover effect */
        footer a.text-white:hover {
            color: #f8f9fa !important;
            text-decoration: underline;
        }
        /* Price tag styling */
        .price-tag {
            font-size: 1.2rem;
            letter-spacing: -0.5px;
        }
        /* Improved button styling */
        .details-btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Shoe Store</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orderHistory.php">My Orders</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo htmlspecialchars($cartCount); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2>Our Shoes Collection</h2>
                <p class="text-muted">Find the perfect pair for every occasion</p>
            </div>
            <div class="col-md-4">
                <form method="get" class="d-flex">
                    <select name="category" class="form-select filter-select me-2">
                        <option value="0">All Categories</option>
                        <?php while ($category = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($categoryFilter == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary filter-btn">Filter</button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card product-card">
                            <?php if ($product['total_stock'] === null || $product['total_stock'] <= 0): ?>
                                <span class="badge bg-danger badge-stock">Out of Stock</span>
                            <?php elseif ($product['total_stock'] < 10): ?>
                                <span class="badge bg-warning text-dark badge-stock">Low Stock</span>
                            <?php endif; ?>
                            
                            <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <span class="badge bg-secondary mb-2 category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <p class="card-text text-success fw-bold price-tag">$<?php echo number_format($product['base_price'], 2); ?></p>
                                <div class="d-grid">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary details-btn">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No products found. Please check back later or try another category.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Shoe Store</h5>
                    <p>Quality footwear for every occasion.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Shoe Street, Fashion City</p>
                        <p><i class="fas fa-phone me-2"></i> (123) 456-7890</p>
                        <p><i class="fas fa-envelope me-2"></i> info@shoestore.com</p>
                    </address>
                </div>
            </div>
            <div class="text-center mt-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Shoe Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>