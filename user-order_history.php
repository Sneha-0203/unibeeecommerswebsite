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
        nav {
            background: linear-gradient(to right, #f12711, #f5af19);
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            width: 100%;
        }

        nav ul li {
            display: flex;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            transition: color 0.3s ease-in-out, background-color 0.3s ease;
            display: flex;
            align-items: center;
        }

        nav a:hover {
            color: #ff5722;
            background-color: rgba(255, 255, 255, 0.1);
        }

        nav a i {
            margin-right: 8px;
            font-size: 16px;
        }

        /* Brand styling */
        nav ul .brand {
            font-size: 22px;
            font-weight: bold;
            color: white;
            margin-right: auto;
            display: flex;
            align-items: center;
        }

        nav ul .brand i {
            margin-right: 8px;
        }

        /* Icon-specific styling */
        nav a .fa-heart {
            color: white;
            transition: color 0.3s ease;
        }

        nav a:hover .fa-heart {
            color: #ff5722;
        }

        nav a .fa-shopping-cart {
            color: white;
            transition: color 0.3s ease;
        }

        nav a:hover .fa-shopping-cart {
            color: #ff5722;
        }
        /* Updated Profile Dropdown Styles */
        #cart-count {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: #ff5722;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Profile dropdown styling */
        .profile-dropdown {
            position: relative;
            cursor: pointer;
        }

        .profile-dropdown a.user-logged-in {
            display: flex;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.1);
            color: white;
            border-radius: 20px;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .profile-dropdown a.user-logged-in:hover {
            background-color: rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-dropdown a.user-logged-in i.fa-user-circle {
            font-size: 18px;
            margin-right: 6px;
        }

        .profile-dropdown i.fa-caret-down {
            margin-left: 6px;
            transition: transform 0.3s ease;
        }

        .profile-dropdown:hover i.fa-caret-down {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            border-radius: 8px;
            margin-top: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid #eee;
            display: none;
            z-index: 1100;
            transform-origin: top center;
            transform: scaleY(0);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .profile-dropdown:hover .dropdown-content {
            display: block;
            transform: scaleY(1);
            opacity: 1;
        }

        .dropdown-content a {
            display: flex !important;
            align-items: center;
            padding: 12px 16px;
            color: #333;
            font-weight: 400 !important;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            color: #ff5722;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
            color: #ff5722;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            nav ul {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            nav ul .brand {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
        }
footer {
    background: #000; /* Black background */
    color: #fff; /* White text */
    padding: 20px 0;
    font-size: 18px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
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
        
 
footer {
    background: #000; /* Black background */
    color: #fff; /* White text */
    padding: 20px 0;
    font-size: 18px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.footer-col h3 {
    margin-bottom: 15px;
    font-size: 22px;
    position: relative;
    padding-bottom: 8px;
    color: #fff; /* White heading */
    text-decoration: underline; /* Underline effect */
    text-decoration-thickness: 3px; /* Thicker underline */
    text-underline-offset: 5px; /* Space between text and underline */
    text-decoration-color: #ff9800; /* Orange underline */
    transition: color 0.3s ease, text-decoration-color 0.3s ease;
}

/* Hover Effect for Headings */
.footer-col h3:hover {
    color: #ff9800; /* Change text color to orange */
    text-decoration-color: #fff; /* Change underline to white */
}

.footer-col ul {
    list-style: none;
    padding: 0;
}

.footer-col ul li {
    margin-bottom: 10px;
}

/* Links */
.footer-col ul li a {
    font-size: 18px;
    color: #ccc; /* Light gray for contrast */
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
}

/* Hover Effect for Links */
.footer-col ul li a:hover {
    color: #ff9800; /* Bright orange on hover */
    padding-left: 5px;
}

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .social-links a i {
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            transform: scale(1.2) translateY(-5px);
        }

        .social-links a:nth-child(1) i { color: #1877F2; }
        .social-links a:nth-child(2) i { color: #1DA1F2; }
        .social-links a:nth-child(3) i { color: #C13584; }
        .social-links a:nth-child(4) i { color: #E60023; }
        .social-links a:nth-child(5) i { color: #25D366; }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .order-status-card .card-header {
    background-color: #ffe8cc;
    border-bottom: 2px solid #ffa94d;
    color: #e65100;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    font-size: 18px;
    border-radius: 50%;
    background-color: #ffcc80;
    color: #fff;
    transition: transform 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.2);
}

/* Orange variations */
.bg-orange-pending { background-color: #ffb74d; }
.bg-orange-processing { background-color: #ff9800; }
.bg-orange-shipped { background-color: #fb8c00; }
.bg-orange-delivered { background-color: #ef6c00; }
.bg-orange-cancelled { background-color: #e65100; }

.order-status-card .card-body .d-flex strong {
    color: #5d4037;
}

.order-status-card .card-body p {
    color: #8d6e63;
}
/* Orange color utilities */
.bg-light-orange {
    background-color: #ffe5b4 !important;
}

.text-orange {
    color: #ff7f00 !important;
}

.btn-orange {
    background-color: #ff7f00;
    color: white;
    border: none;
    transition: background 0.3s ease;
}

.btn-orange:hover {
    background-color: #e76f00;
    color: #fff;
}

/* Orange for shipped status */
.bg-orange {
    background-color: #ffa500;
}

/* Badge styling */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    font-size: 0.85rem;
    border-radius: 30px;
}

    </style>
</head>
<body>

     <nav>
        
            
        <ul>

        <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
        <li><a href="wishlist.php"><i class="fas fa-shopping-cart"></i> cart</a></li>
        <a href="user-order_history.php"><i class="fas fa-shopping-bag"></i> My Orders</a>                               

        <li class="profile-dropdown">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="#" id="profile-btn" class="user-logged-in">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo $_SESSION['user_name']; ?> 
                        <i class="fas fa-caret-down"></i>
                    </a>
                <?php else: ?>
                    <a href="#" id="profile-btn">
                        <i class="fas fa-user"></i> Profile 
                        <i class="fas fa-caret-down"></i>
                    </a>
                <?php endif; ?>
                <div class="dropdown-content">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="#" id="login-dropdown-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="#" id="signup-dropdown-btn"><i class="fas fa-user-plus"></i> Sign Up</a>
                        <a href="admin_login.php"><i class="fas fa-lock"></i> Admin Login</a>
                    <?php else: ?>
                        <a href="profile.php"><i class="fas fa-id-card"></i> My Profile</a>
                         <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </nav>

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

    <!-- Order History Card -->
    <div class="card mb-4 shadow-sm border-0 rounded-4">
        <div class="card-header bg-gradient bg-light-orange text-dark fw-semibold">
            <i class="fas fa-clipboard-list me-2"></i>Order History
        </div>
        <div class="card-body">
            <?php if (!empty($orders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-warning">
                            <tr>
                                <th><i class="fas fa-hashtag me-1"></i> Order #</th>
                                <th><i class="far fa-calendar-alt me-1"></i> Date</th>
                                <th><span>₹</span> Total</th>
                                <th><i class="fas fa-tag me-1"></i> Status</th>
                                <th><i class="fas fa-cog me-1"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php 
                                // Custom order status styles with orange theme
                                $statusClass = '';
                                $statusIcon = '';
                                switch(strtolower($order['status'])) {
                                    case 'pending':
                                        $statusClass = 'bg-light text-warning border border-warning';
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'processing':
                                        $statusClass = 'bg-warning text-dark';
                                        $statusIcon = 'fa-cog fa-spin';
                                        break;
                                    case 'shipped':
                                        $statusClass = 'bg-orange text-white';
                                        $statusIcon = 'fa-truck';
                                        break;
                                    case 'delivered':
                                        $statusClass = 'bg-success text-white';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-danger text-white';
                                        $statusIcon = 'fa-times-circle';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary text-white';
                                        $statusIcon = 'fa-question-circle';
                                }
                                ?>
                                <tr>
                                    <td class="fw-bold text-orange">#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td class="fw-semibold">₹<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="badge px-3 py-2 rounded-pill <?php echo $statusClass; ?>">
                                            <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orderDetails.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-orange shadow-sm">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-orders text-center py-5">
                    <i class="fas fa-shopping-bag fa-3x text-muted"></i>
                    <h3 class="mt-3">No Orders Yet</h3>
                    <p class="text-muted">You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn btn-orange mt-3">
                        <i class="fas fa-tags me-1"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>




        
<!-- Order Status Guide -->
<div class="card mb-4 order-status-card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Status Guide</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="status-badge bg-orange-pending me-2">
                        <i class="fas fa-clock"></i>
                    </span>
                    <div>
                        <strong>Pending</strong>
                        <p class="mb-0 small">Your order has been placed but not yet processed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="status-badge bg-orange-processing me-2">
                        <i class="fas fa-cog"></i>
                    </span>
                    <div>
                        <strong>Processing</strong>
                        <p class="mb-0 small">Your order is being processed in our warehouse</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="status-badge bg-orange-shipped me-2">
                        <i class="fas fa-truck"></i>
                    </span>
                    <div>
                        <strong>Shipped</strong>
                        <p class="mb-0 small">Your order is on its way to you</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="status-badge bg-orange-delivered me-2">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <div>
                        <strong>Delivered</strong>
                        <p class="mb-0 small">Your order has been delivered successfully</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="status-badge bg-orange-cancelled me-2">
                        <i class="fas fa-times-circle"></i>
                    </span>
                    <div>
                        <strong>Cancelled</strong>
                        <p class="mb-0 small">Your order has been cancelled</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<!-- Footer Section -->
<footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>UNIBEE</h3>
                    <p>Your ultimate destination for stylish and comfortable footwear.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-home" style="color: #4CAF50;"></i> Home</a></li>
                        <li><a href="product.php"><i class="fas fa-shopping-bag" style="color: #FF9800;"></i> Products</a></li>
                        <li><a href="aboutus.php"><i class="fas fa-info-circle" style="color: #2196F3;"></i> About Us</a></li>
                        <li><a href="contact.html"><i class="fas fa-envelope" style="color: #E91E63;"></i> Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="faq.php"><i class="fas fa-question-circle" style="color: #FF5722;"></i> FAQ</a></li>
                        <li><a href="shipping-policy.php"><i class="fas fa-shipping-fast" style="color: #FF9800;"></i> Shipping Policy</a></li>
                        <li><a href="return-policy.php"><i class="fas fa-undo" style="color: #E91E63;"></i> Returns & Exchanges</a></li>
                        <li><a href="terms.php"><i class="fas fa-file-contract" style="color: #9C27B0;"></i> Terms & Conditions</a></li>
                    
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><a href="https://maps.google.com?q=123 Shoe Street, Fashion City" target="_blank"><i class="fas fa-map-marker-alt" style="color: #FFC107;"></i> 123 Shoe Street, Fashion City</a></li>
                        <li><a href="tel:+12345678900"><i class="fas fa-phone" style="color: #4CAF50;"></i> +1 234 567 8900</a></li>
                        <li><a href="mailto:info@unibee.com"><i class="fas fa-envelope" style="color: #2196F3;"></i> info@unibee.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 UNIBEE. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</html>
<?php
// Close the database connection
$conn->close();
?>