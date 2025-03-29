<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$loggedIn) {
    $_SESSION['error_message'] = "Please log in to view your order history.";
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";  // Usually localhost
$username = "root";         // Default XAMPP username
$password = "";             // Default XAMPP password is empty
$dbname = "shoe_store";     // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user ID from session
$userId = $_SESSION['user_id'];

// Fetch user's orders from database
$query = "SELECT id as order_id, created_at as order_date, total_amount as total, status 
          FROM orders 
          WHERE user_id = ? 
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Store orders in array
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Your Ecommerce Store</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #ff006e;
            --accent-color: #fb5607;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .navbar {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .page-header {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .table th {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2a75e6;
            border-color: #2a75e6;
        }
        
        .btn-info {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #e04c03;
            border-color: #e04c03;
            color: white;
        }
        
        .status-badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
        }
        
        .empty-orders {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
        }
        
        .empty-orders i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .footer {
            margin-top: 4rem;
            padding: 2rem 0;
            background-color: var(--dark-bg);
            color: #f8f9fa;
        }
        
        .footer a {
            color: #f8f9fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .footer a:hover {
            color: var(--primary-color);
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            margin-right: 10px;
            transition: background-color 0.2s ease;
        }
        
        .social-links a:hover {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints me-2"></i>ShoeStore
            </a>
            
            <!-- Responsive menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-2" action="search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="query" placeholder="Search products..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- User Account and Cart -->
                <div class="d-flex">
                    <?php if ($loggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle me-2" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-circle me-2"></i>My Account</a></li>
                                <li><a class="dropdown-item active" href="orderHistory.php"><i class="fas fa-history me-2"></i>Order History</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                    
                    <a href="cart.php" class="btn btn-outline-light position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        // Display cart item count if exists
                        if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                            echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' 
                                . count($_SESSION['cart']) . '</span>';
                        }
                        ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-history me-2"></i>Your Order History</h1>
                    <p class="lead mb-0">Track and manage all your past purchases</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="products.php" class="btn btn-light">
                        <i class="fas fa-shopping-bag me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="container">
        <!-- Flash Messages/Alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Order History Content Starts Here -->
        <div class="card mb-4">
            <div class="card-body">
                <?php if (!empty($orders)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i> Order #</th>
                                    <th><i class="far fa-calendar-alt me-1"></i> Date</th>
                                    <th><i class="fas fa-dollar-sign me-1"></i> Total</th>
                                    <th><i class="fas fa-tag me-1"></i> Status</th>
                                    <th><i class="fas fa-cog me-1"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td class="fw-bold">$<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            $statusIcon = '';
                                            switch(strtolower($order['status'])) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning text-dark';
                                                    $statusIcon = 'fa-clock';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'bg-info text-dark';
                                                    $statusIcon = 'fa-cog fa-spin';
                                                    break;
                                                case 'shipped':
                                                    $statusClass = 'bg-primary';
                                                    $statusIcon = 'fa-truck';
                                                    break;
                                                case 'delivered':
                                                    $statusClass = 'bg-success';
                                                    $statusIcon = 'fa-check-circle';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'bg-danger';
                                                    $statusIcon = 'fa-times-circle';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                                    $statusIcon = 'fa-question-circle';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="orderDetails.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                <?php if (strtolower($order['status']) != 'delivered' && strtolower($order['status']) != 'cancelled'): ?>
                                                    <a href="trackOrder.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-truck me-1"></i> Track
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-orders text-center">
                        <i class="fas fa-shopping-bag"></i>
                        <h3 class="mt-3">No Orders Yet</h3>
                        <p class="text-muted">You haven't placed any orders yet.</p>
                        <a href="products.php" class="btn btn-primary mt-3">
                            <i class="fas fa-tags me-1"></i> Browse Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Status Guide -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Status Guide</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="status-badge bg-warning text-dark me-2">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div>
                                <strong>Pending</strong>
                                <p class="mb-0 small text-muted">Your order has been placed but not yet processed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="status-badge bg-info text-dark me-2">
                                <i class="fas fa-cog"></i>
                            </span>
                            <div>
                                <strong>Processing</strong>
                                <p class="mb-0 small text-muted">Your order is being processed in our warehouse</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="status-badge bg-primary me-2">
                                <i class="fas fa-truck"></i>
                            </span>
                            <div>
                                <strong>Shipped</strong>
                                <p class="mb-0 small text-muted">Your order is on its way to you</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="status-badge bg-success me-2">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <div>
                                <strong>Delivered</strong>
                                <p class="mb-0 small text-muted">Your order has been delivered successfully</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="status-badge bg-danger me-2">
                                <i class="fas fa-times-circle"></i>
                            </span>
                            <div>
                                <strong>Cancelled</strong>
                                <p class="mb-0 small text-muted">Your order has been cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">ShoeStore</h5>
                    <p class="mb-3">Your one-stop shop for all your footwear needs.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="about.php"><i class="fas fa-angle-right me-2"></i>About Us</a></li>
                        <li class="mb-2"><a href="contact.php"><i class="fas fa-angle-right me-2"></i>Contact Us</a></li>
                        <li class="mb-2"><a href="terms.php"><i class="fas fa-angle-right me-2"></i>Terms & Conditions</a></li>
                        <li class="mb-2"><a href="privacy.php"><i class="fas fa-angle-right me-2"></i>Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-5">
                    <h5 class="mb-3">Contact Info</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>123 Shoe Street, Fashion District, City</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>(123) 456-7890</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>info@shoestore.com</li>
                        <li class="mb-2"><i class="fas fa-clock me-2"></i>Mon-Fri: 9am - 6pm, Sat: 10am - 4pm</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ShoeStore. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Designed with <i class="fas fa-heart text-danger"></i> for shoe lovers</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>