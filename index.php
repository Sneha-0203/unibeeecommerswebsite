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
    <title>UNIBEE- Your Ultimate Shoe Destination</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF5500;
            --secondary-color: #ff6b6b;
            --third-color: #rgb(94, 92, 92)33;
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
        
      
        
        /* Enhanced Featured Products Section */
        .section-title {
            text-align: center;
            margin: 50px 0 30px;
            font-size: 50px;
            font-weight: 700;
            color: #ff5500;
            position: relative;
            transform: perspective(500px) rotateX(5deg);
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
            background-color: #FF5500;

            padding: 8px 16px;
            font-size: 14px;
        }
        .product-actions .btn:hover {
    background-color:rgb(94, 92, 92);
    color: white;
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
        
        
        /* Newsletter Section */
        .newsletter {
            background: linear-gradient(to right, #f12711, #f5af19);
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
     /* Shop By Category Section */
.featured-categories {
    padding: 60px 0;
    margin-left: -25%;
}

.featured-categories h2 {
    text-align: center;
    margin-bottom: 40px;
    font-size: 32px;
    font-weight: 700;
    color: var(--dark-color);
    position: relative;
    margin-left: 20%;
    
    
}

.featured-categories h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: var(--primary-color);
}
.featured-categories .fix{
    display: flex;
    gap: 13%;
    
}

.category-card {
    border: none !important;
    overflow: hidden;
    border-radius: 15px !important;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.4s ease !important;
    width: 200%;
    margin-right: 3%;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.category-icon {
    margin: 0 auto 20px;
    background-color: rgba(255, 255, 255, 0.2);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    
    
    transition: transform 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
}

.category-card h4 {
    color: white;
    font-weight: 200;
    margin-bottom: 10px;
    margin-left: 3%;
    transition: transform 0.3s ease;
}

.category-card:hover h4 {
    transform: translateY(-5px);
}

.category-card p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
    margin-bottom: 20px;
    margin-left: 3%;
    transition: opacity 0.3s ease;
}

.category-card:hover p {
    opacity: 1;
}

.category-card .btn {
    background-color: white;
    color: var(--dark-color);
    font-weight: 200;
    padding: 5px 12px;
    border-radius: 30px;
    transition: all 0.3s ease;
    border: none;
    margin-bottom: 3%;
    margin-left: 3%;
}

.category-card:hover .btn {
    background-color: var(--primary-color);
    color: white;
    transform: translateY(3px);
}

.category-card .btn i {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.category-card:hover .btn i {
    transform: translateX(5px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .featured-categories {
        padding: 40px 0;
    }
    
    .category-icon {
        width: 60px;
        height: 60px;
    }
    
    .category-card h4 {
        font-size: 18px;
    }
    
    .category-card p {
        font-size: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .category-card .btn {
        padding: 6px 14px;
        font-size: 12px;
    }
    
}

/* For extra small screens */
@media (max-width: 576px) {
    .category-icon {
        width: 50px;
        height: 50px;
        margin-bottom: 10px;
    }
    
    .category-card h4 {
        font-size: 16px;
    }
    
    .category-card p {
        display: none;
    }
    
    .category-card .btn {
        padding: 5px 10px;
        font-size: 10px;
    }
}
.preview-container {
    padding: 0; /* Optional: reduce space */
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;     /* Full viewport height */
    width: 100vw;      /* Full viewport width */
    margin: 0;
}

        
.hero {
    background-color: white;
    padding: 80px 0;
    width: 85%;        /* Full screen width */
    height: 85%;       /* Full screen height */
    max-width: 100vw;    /* Remove fixed 1200px width limit */
    margin: 0 auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-radius: 0;    /* Optional: remove curve if you want edge-to-edge */
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
}

        
        .hero-content {
            padding: 0 40px;
            max-width: 55%;
            position: relative;
            z-index: 2;
            line-height: 0.9;
        }
        
        .hero-tag {
            font-size: 30px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 5px;
            color: #4a4a4a;
        }
        .hero-tag1 {
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 5px;
            color: #f3f8fafd;
             text-align:center ;
        }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-weight: 700;
            font-size: 180px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #000;
            margin-top: 0;
            margin-bottom: 5px;
            line-height: 0.9;
            position: relative;
            display: inline-block;
            transform: perspective(500px) rotateX(5deg);
            text-shadow: 
                0 2px 0 #ff5500,
                0 4px 0 #ff5500,
                0 6px 0 #ff5500,
                0 8px 0 rgba(0,0,0,0.3);
        }
        
        .hero-description {
            font-size: 20px;
            line-height: 1.2;
            margin-top: 10px;
            margin-bottom: 20px;
            color: #616161;
            max-width: 600px;
        }
        
        .cta-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .primary-btn {
    background-color: #FF5500;
    color: white;
    border: none;
    padding: 14px 28px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.primary-btn:hover {
    background-color: rgb(94, 92, 92);
    color: white; /* Ensure text remains white on hover */
}

        
        
        
        /* Updated Shoe and Circle Styling */
        .orange-circle {
            position: absolute;
            width: 850px;
            height: 1000px;
            background: radial-gradient(circle, #FFB347 0%, #FF7A00 60%, #FF5500 100%);
            border-radius: 60%;
            opacity: 0.9;
            right: -130px;
            top: 50%;
            
            transform: translateY(-50%);
            z-index: 0;
            box-shadow: 0 10px 30px rgba(255, 85, 0, 0.3);
        }
        
        .shoe-container {
            position: absolute;
            right: -1%;
            top: 60%;
            transform: translateY(-50%);
            z-index: 1;
            transition: all 0.5s ease;
        }
        .shoe-image {
            width: 800px;
            height: auto;
            transform: rotate(15deg);
            filter: drop-shadow(5px 15px 25px rgba(0, 0, 0, 0.5));
            position: relative;
            z-index: 1;
            transition: transform 0.5s ease, filter 0.5s ease;
            object-fit: contain;
        }
        
        .shoe-container:hover .shoe-image {
            transform: rotate(20deg) scale(1.05);
            filter: drop-shadow(8px 20px 30px rgba(0, 0, 0, 0.6));
        }
        
        @media (max-width: 1100px) {
            .hero-content {
                max-width: 50%;
            }
            
            .shoe-image {
                width: 400px;
            }
            
            .orange-circle {
                width: 420px;
                height: 420px;
                right: -80px;
            }
        }
        
        @media (max-width: 992px) {
            .hero-content {
                max-width: 100%;
                margin-bottom: 280px;
                text-align: center;
            }
            
            .shoe-container {
                position: absolute;
                right: 0;
                left: 0;
                top: auto;
                bottom: -60px;
                transform: none;
                margin: 0 auto;
                text-align: center;
            }
            
            .orange-circle {
                width: 380px;
                height: 380px;
                right: 0;
                left: 0;
                margin: 0 auto;
                bottom: -130px;
                top: auto;
                transform: none;
            }
            
            .shoe-image {
                width: 350px;
                transform: rotate(0deg);
            }
            
            .shoe-container:hover .shoe-image {
                transform: rotate(5deg) scale(1.05);
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }
            
            .hero-content {
                padding: 0 20px;
                margin-bottom: 240px;
            }
            
            .shoe-image {
                width: 280px;
            }
            
            .orange-circle {
                width: 320px;
                height: 320px;
                bottom: -100px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content {
                margin-bottom: 200px;
            }
            
            .shoe-image {
                width: 220px;
            }
            
            .orange-circle {
                width: 260px;
                height: 260px;
                bottom: -80px;
            }
            
            .cta-buttons {
                justify-content: center;
            }
        }
    </style>
    
</head>
<body>
    <!-- Header Section -->
    <header>
    <nav>
        
            
                <ul>

                <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
                <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                <li><a href="wishlist.php"><i class="fas fa-shopping-cart"></i> cart</a></li>
                <li><a href="user-order_history.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>                               

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

    <!-- Hero Section -->
    <div class="preview-container">
        <section class="hero">
            <div class="hero-content">
                <div class="hero-tag">Step into Style</div>
                <div class="hero-tag1"> .  </div>
                <h1 class="hero-title">UNIBEE</h1> 
                <div class="hero-description">From comfort to confidence — your feet deserve the best.</div>
                <div class="cta-buttons">
                    <button class="primary-btn"><a href="product.php">Shop Now</a></button>
                </div>
            </div>
            <div class="orange-circle"></div>
            <div class="shoe-container">
                <!-- Using a placeholder image -->
                <img src="uploads/Hero section/pngtree-dropshipping-men-hole-sole-jogging-shoes-png-image_11389148.png" target="_blank" class="shoe-image">
            </div>
        </section>
    </div>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                    <img src="<?php echo !empty($product['image']) ? 'uploads/products/' . htmlspecialchars($product['image']) : 'assets/img/product-placeholder.jpg'; ?>" 
                    class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-description"><?php echo $product['description']; ?></p>
                        <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
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
                <a href="product.php" class="btn">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Featured Categories Section -->
<div class="container my-5">
    <div class="featured-categories">
        <h2 class="text-center mb-4">Shop By Category</h2>
        <div class="fix row g-4 mt-2">
            <?php
            // Get categories for the homepage links
            $categoriesQuery = "SELECT id, name, description FROM categories ORDER BY id";
            $categoriesResult = $conn->query($categoriesQuery);
            
            // Define icons for each category
            $categoryIcons = [
                'Men' => 'fa-male',
                'Women' => 'fa-female',
                'Sports' => 'fa-running',
                'Casual' => 'fa-shoe-prints',
                'Formal' => 'fa-briefcase'
            ];
            
            // Define background colors for each category card
            $categoryColors = [
                'Men' => '#3498db',
                'Women' => '#e84393',
                'Sports' => '#2ecc71',
                'Casual' => '#f39c12',
                'Formal' => '#34495e'
            ];
            
            while ($category = $categoriesResult->fetch_assoc()): 
                // Get the appropriate icon or default to shoe-prints
                $icon = isset($categoryIcons[$category['name']]) ? $categoryIcons[$category['name']] : 'fa-shoe-prints';
                // Get the appropriate color or default to a blue shade
                $bgColor = isset($categoryColors[$category['name']]) ? $categoryColors[$category['name']] : '#3498db';
            ?>
                <div class="col-lg-4 col-md-6 col-6">
                    <a href="product.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card category-card h-100" style="border: none; overflow: hidden; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                            <div class="card-body text-center" style="background: linear-gradient(135deg, <?php echo $bgColor; ?>, <?php echo $bgColor; ?>80);">
                                <div class="category-icon" style="margin-bottom: 15px; background-color: rgba(255,255,255,0.2); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                    <i class="fas <?php echo $icon; ?>" style="font-size: 2.5rem; color: white;"></i>
                                </div>
                                <h4 class="card-title" style="color: white; font-weight: 600;"><?php echo htmlspecialchars($category['name']); ?></h4>
                                <p class="text-white-50 mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="btn btn-light rounded-pill" style="font-weight: 500;">
                                    Shop Now <i class="fas fa-arrow-right ms-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<!-- Newsletter Section -->
<section class="newsletter" id="newsletter">
    <div class="container">
        <h2>Subscribe to Our Newsletter</h2>
        <p>Stay updated with our latest arrivals, exclusive offers, and shoe care tips.</p>
        
        <?php if (isset($_SESSION['newsletter_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo $_SESSION['newsletter_success']; 
                    unset($_SESSION['newsletter_success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['newsletter_error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo $_SESSION['newsletter_error']; 
                    unset($_SESSION['newsletter_error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form class="newsletter-form" action="newsletter_subscribe.php" method="POST">
            <input type="email" name="email" placeholder="Your Email Address" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
</section>

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