<?php
// Start session
session_start();

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "shoe_store";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error variables
$login_error = "";
$signup_error = "";
$success_message = "";

// Process Login Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, name, password FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
        } else {
            $login_error = "Invalid password";
        }
    } else {
        $login_error = "Email not found";
    }
}

// Process Signup Form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if ($password !== $confirm_password) {
        $signup_error = "Passwords do not match";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $signup_error = "Email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed_password')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $success_message = "Account created successfully! Please login.";
            } else {
                $signup_error = "Error: " . $conn->error;
            }
        }
    }
}

// Fetch featured products - 
// Using all products since there's no featured field in your table
// You can add a WHERE condition for featured products when you add that field
$featured_products = [];
$products_sql = "SELECT id, name, description, base_price as price, image, category_id, created_at FROM products LIMIT 8";
$products_result = $conn->query($products_sql);

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
} else {
    // No products found
    $featured_products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShoeStore - Your Ultimate Shoe Destination</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #ff6b6b;
            --dark-color: #333;
            --light-color: #f4f4f4;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
        }
        
        .brand a {
            text-decoration: none;
        }
        
        .brand h1 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }
        
        nav ul li a {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        nav ul li a:hover {
            color: var(--primary-color);
        }
        
        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        nav ul li a:hover::after {
            width: 100%;
        }
        /* Updated Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
            cursor: pointer;
        }

        .profile-dropdown a.user-logged-in {
            display: flex;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }

        .profile-dropdown a.user-logged-in:hover {
            background-color: #2a75e6;
            box-shadow: 0 2px 10px rgba(42, 117, 230, 0.4);
        }

        .profile-dropdown a.user-logged-in i.fa-user-circle {
            font-size: 18px;
            margin-right: 6px;
        }

        .profile-dropdown a:not(.user-logged-in) {
            display: flex;
            align-items: center;
        }

        .profile-dropdown a:not(.user-logged-in) i {
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
            min-width: 200px;
            border-radius: 8px;
            margin-top: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid #eee;
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
            font-weight: 400 !important;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
        }
        
        #cart-count {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        /* Banner Styles */
        .banner {
            background: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5)), url('images/banner.jpg');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }
        
        .banner-content {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .banner-content h2 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .banner-content p {
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: #2a75e6;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(58, 134, 255, 0.3);
        }
        
        /* Enhanced Featured Products Section */
        .section-title {
            text-align: center;
            margin: 50px 0 30px;
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        /* Enhanced Product Card */
        .product-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .product-card:hover .product-image::before {
            opacity: 1;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.1);
        }
        
        .product-tag {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-info h3 {
            margin-bottom: 10px;
            font-size: 18px;
            color: var(--dark-color);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .product-card:hover .product-info h3 {
            color: var(--primary-color);
        }
        
        .product-description {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 22px;
            margin-bottom: 20px;
            display: block;
        }
        
        .product-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-actions .btn {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .add-to-cart {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            color: var(--dark-color);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-to-cart:hover {
            background-color: var(--primary-color);
            color: white;
            transform: rotate(360deg);
        }
        
        .view-more {
            text-align: center;
            margin: 50px 0;
        }
        
        /* Categories Section */
        /* Categories Section */
        .categories {
            padding: 50px 0;
            background-color: #f5f5f5;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            position: relative;
            height: 300px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .category-card:hover img {
            transform: scale(1.1);
        }
        
        .category-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 20px;
            color: white;
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .category-card:hover .category-content {
            transform: translateY(-5px);
        }
        
        .category-content h3 {
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 600;
        }
        
        /* Newsletter Section */
        .newsletter {
            background-color: var(--primary-color);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .newsletter h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .newsletter p {
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 30px 0 0 30px;
            font-size: 16px;
        }
        
        .newsletter-form button {
            padding: 15px 25px;
            background-color: var(--dark-color);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background-color: var(--secondary-color);
        }
        
        /* Footer Styles */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-col h3 {
            margin-bottom: 20px;
            font-size: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 10px;
        }
        
        .footer-col ul li a {
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-col ul li a:hover {
            color: white;
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
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            width: 90%;
            max-width: 450px;
            border-radius: 15px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            padding: 30px;
            animation: slideIn 0.4s;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close:hover {
            color: var(--dark-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(58, 134, 255, 0.3);
            outline: none;
        }
        
        .modal h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .modal p {
            text-align: center;
            margin-top: 15px;
        }
        
        .modal a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .modal a:hover {
            text-decoration: underline;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .alert-success {
            background-color: #e3f6e8;
            color: var(--success-color);
            border: 1px solid #c5e9d5;
        }
        
        .alert-danger {
            background-color: #fce8e8;
            color: var(--danger-color);
            border: 1px solid #f9d0d0;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .brand h1 {
                font-size: 22px;
            }
            
            nav ul {
                gap: 15px;
            }
            
            .banner-content h2 {
                font-size: 32px;
            }
            
            .banner-content p {
                font-size: 16px;
            }
            
            .modal-content {
                margin: 20% auto;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-form input {
                border-radius: 30px;
                margin-bottom: 10px;
            }
            
            .newsletter-form button {
                border-radius: 30px;
            }
        }
        
        @media (max-width: 576px) {
            header .container {
                flex-direction: column;
                padding: 10px;
            }
            
            nav ul {
                margin-top: 15px;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .banner {
                height: 400px;
            }
            
            .banner-content h2 {
                font-size: 26px;
            }
            
            .section-title {
                font-size: 26px;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .product-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="brand">
                <a href="index.php">
                    <h1>ShoeStore</h1>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart <span id="cart-count">0</span></a></li>
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
                                <a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Success Message Display -->
    <?php if (!empty($success_message)): ?>
    <div class="container" style="margin-top: 20px;">
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Login Form -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Login</h2>
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            <form id="login-form" action="index.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login_submit" class="btn">Login</button>
                <p>Don't have an account? <a href="#" id="signup-link">Sign up</a></p>
                <p><a href="admin_login.php">Admin Login</a></p>
            </form>
        </div>
    </div>

    <!-- Modal Sign Up Form -->
    <div id="signup-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Sign Up</h2>
            <?php if (!empty($signup_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $signup_error; ?>
                </div>
            <?php endif; ?>
            <form id="signup-form" action="index.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" name="signup_submit" class="btn">Sign Up</button>
                <p>Already have an account? <a href="#" id="login-link">Login</a></p>
            </form>
        </div>
    </div>

    <!-- Banner Section -->
    <section class="banner">
        <div class="container">
            <div class="banner-content">
                <h2>Step into Style with ShoeStore</h2>
                <p>Discover our exclusive collection of shoes for every occasion. From casual to formal, we've got you covered with the latest trends and timeless classics.</p>
                <a href="products.php" class="btn">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        <span class="product-tag">New</span>
                    </div>
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-description"><?php echo $product['description']; ?></p>
                        <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                        <div class="product-actions">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                            <button class="add-to-cart" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($featured_products)): ?>
                <div style="text-align: center; grid-column: 1 / -1;">
                    <p>No products available at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="view-more">
                <a href="products.php" class="btn">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="category-grid">
                <div class="category-card">
                    <img src="images/categories/casual.jpg" alt="Casual Shoes">
                    <div class="category-content">
                        <h3>Casual Shoes</h3>
                        <a href="products.php?category=1" class="btn">Shop Now</a>
                    </div>
                </div>
                <div class="category-card">
                    <img src="images/categories/sports.jpg" alt="Sports Shoes">
                    <div class="category-content">
                        <h3>Sports Shoes</h3>
                        <a href="products.php?category=2" class="btn">Shop Now</a>
                    </div>
                </div>
                <div class="category-card">
                    <img src="images/categories/formal.jpg" alt="Formal Shoes">
                    <div class="category-content">
                        <h3>Formal Shoes</h3>
                        <a href="products.php?category=3" class="btn">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <div class="container">
            <h2>Subscribe to Our Newsletter</h2>
            <p>Stay updated with our latest arrivals, exclusive offers, and shoe care tips.</p>
            <form class="newsletter-form">
                <input type="email" placeholder="Your Email Address" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>ShoeStore</h3>
                    <p>Your ultimate destination for stylish and comfortable footwear. We bring you the best brands and designs from around the world.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="shipping.php">Shipping Policy</a></li>
                        <li><a href="returns.php">Returns & Exchanges</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Shoe Street, Fashion City</li>
                        <li><i class="fas fa-phone"></i> +1 234 567 8900</li>
                        <li><i class="fas fa-envelope"></i> info@shoestore.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 ShoeStore. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Get modal elements
        const loginModal = document.getElementById('login-modal');
        const signupModal = document.getElementById('signup-modal');
        
        // Get buttons that open the modal
        const loginBtn = document.getElementById('login-dropdown-btn');
        const signupBtn = document.getElementById('signup-dropdown-btn');
        const loginLink = document.getElementById('login-link');
        const signupLink = document.getElementById('signup-link');
        
        // Get the <span> elements that close the modals
        const closeButtons = document.getElementsByClassName('close');
        
        // Function to open modals
        if (loginBtn) {
            loginBtn.onclick = function() {
                loginModal.style.display = 'block';
            }
        }
        
        if (signupBtn) {
            signupBtn.onclick = function() {
                signupModal.style.display = 'block';
            }
        }
        
        if (loginLink) {
            loginLink.onclick = function(e) {
                e.preventDefault();
                signupModal.style.display = 'none';
                loginModal.style.display = 'block';
            }
        }
        
        if (signupLink) {
            signupLink.onclick = function(e) {
                e.preventDefault();
                loginModal.style.display = 'none';
                signupModal.style.display = 'block';
            }
        }
        
        // Close modals when clicking on X
        for (let i = 0; i < closeButtons.length; i++) {
            closeButtons[i].onclick = function() {
                loginModal.style.display = 'none';
                signupModal.style.display = 'none';
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == loginModal) {
                loginModal.style.display = 'none';
            }
            if (event.target == signupModal) {
                signupModal.style.display = 'none';
            }
        }
        
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        const cartCount = document.getElementById('cart-count');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                // AJAX request to add item to cart
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        cartCount.textContent = data.cart_count;
                        
                        // Show notification
                        alert('Product added to cart!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
        
        // Check for success or error messages from PHP
        <?php if (!empty($login_error) || !empty($signup_error)): ?>
            <?php if (!empty($login_error)): ?>
                loginModal.style.display = 'block';
            <?php endif; ?>
            
            <?php if (!empty($signup_error)): ?>
                signupModal.style.display = 'block';
            <?php endif; ?>
        <?php endif; ?>
        
        // Initialize cart count on page load
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                cartCount.textContent = data.count;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    </script>
</body>
</html>