<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection
require_once 'config/database.php';

// Get user information
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission for profile update
$updateMsg = '';
$updateMsgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    // Check if name is empty
    if (empty($name)) {
        $errors[] = "Name cannot be empty";
    }
    
    // Check if email is valid
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email already exists (except for current user)
        $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->bind_param("si", $email, $userId);
        $checkEmailStmt->execute();
        $emailResult = $checkEmailStmt->get_result();
        if ($emailResult->num_rows > 0) {
            $errors[] = "Email address is already in use";
        }
        $checkEmailStmt->close();
    }
    
    // If password is being changed
    $passwordChanged = false;
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        // Check new password length
        if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        }
        
        $passwordChanged = true;
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        // Start with basic update
        $updateQuery = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $updateParams = ["ssi", $name, $email, $userId];
        
        // If password is changed, include it in the update
        if ($passwordChanged && !empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
            $updateParams = ["sssi", $name, $email, $hashedPassword, $userId];
        }
        
        // Execute update
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param(...$updateParams);
        $updateResult = $updateStmt->execute();
        
        if ($updateResult) {
            $updateMsg = "Profile updated successfully!";
            $updateMsgType = "success";
            
            // Update session name
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $updateMsg = "Error updating profile: " . $conn->error;
            $updateMsgType = "danger";
        }
        
        $updateStmt->close();
    } else {
        $updateMsg = "Please fix the following errors: <ul>";
        foreach ($errors as $error) {
            $updateMsg .= "<li>$error</li>";
        }
        $updateMsg .= "</ul>";
        $updateMsgType = "danger";
    }
}

// Get cart count
$cartQuery = "SELECT SUM(quantity) as cart_count FROM cart WHERE user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();
$cartData = $cartResult->fetch_assoc();
$cartCount = $cartData['cart_count'] ?: 0;

// Get wishlist count
$wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist_items wi 
                  INNER JOIN wishlists w ON wi.wishlist_id = w.id 
                  WHERE w.user_id = ?";
$wishlistStmt = $conn->prepare($wishlistQuery);
$wishlistStmt->bind_param("i", $userId);
$wishlistStmt->execute();
$wishlistResult = $wishlistStmt->get_result();
$wishlistData = $wishlistResult->fetch_assoc();
$wishlistCount = $wishlistData['wishlist_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - UNIBEE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
/* Profile Page Specific Styles */
.profile-container {
    max-width: 800px;
    margin: 30px auto;
    background-color: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.profile-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.profile-header .avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: #ff5722;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 15px;
    color: #fff;
    font-size: 45px;
}

.profile-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #333;
}

.profile-subtitle {
    color: #777;
    font-size: 16px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
    color: #4a4a4a;
}

.form-control {
    border-radius: 8px;
    padding: 12px 15px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #ff5722;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.password-section {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    margin-top: 30px;
    border: 1px solid #eee;
}

.password-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #4a4a4a;
}

.btn-primary {
    background-color: #ff5722;
    border-color: #ff5722;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #3a7ec2;
    border-color: #3a7ec2;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(74, 144, 226, 0.2);
}

.alert {
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 25px;
}

.btn-cancel {
    background-color: #f0f0f0;
    border: none;
    color: #777;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-right: 10px;
}

.btn-cancel:hover {
    background-color: #e0e0e0;
    color: #555;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
}

/* User info section */
.user-info-box {
    background-color: #f8fcff;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 25px;
    border-left: 4px solid #4a90e2;
}

.user-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.user-info-item i {
    width: 30px;
    color: #4a90e2;
    font-size: 18px;
}

.user-info-item span {
    font-weight: 500;
    color: #555;
}

.user-info-label {
    font-weight: 600;
    color: #777;
    width: 120px;
}

/* Footer styling */
footer {
    background: #000; /* Black background */
    color: #fff; /* White text */
    padding: 20px 0;
    font-size: 18px;
    margin-top: 40px;
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

/* Responsive styling */
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
    
    .profile-container {
        padding: 20px;
        margin: 15px;
    }
}
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <ul>
            <span class="brand"><i class="fas fa-shoe-prints"></i> UNIBEE</span>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="product.php"><i class="fas fa-tags"></i> Products</a></li>
            <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist <?php if($wishlistCount > 0): ?><span id="wishlist-count"><?php echo $wishlistCount; ?></span><?php endif; ?></a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart <?php if($cartCount > 0): ?><span id="cart-count"><?php echo $cartCount; ?></span><?php endif; ?></a></li>
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

    <!-- Main Content -->
    <div class="container">
        <div class="profile-container">
            <?php if(!empty($updateMsg)): ?>
                <div class="alert alert-<?php echo $updateMsgType; ?>">
                    <?php echo $updateMsg; ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-header">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h1 class="profile-title"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="profile-subtitle">Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="user-info-box">
                <div class="user-info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="user-info-label">Email:</div>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="user-info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="user-info-label">Joined:</div>
                    <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="user-info-item">
                    <i class="fas fa-heart"></i>
                    <div class="user-info-label">Wishlist:</div>
                    <span><?php echo $wishlistCount; ?> items</span>
                </div>
                <div class="user-info-item">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="user-info-label">Cart:</div>
                    <span><?php echo $cartCount; ?> items</span>
                </div>
            </div>
            
            <h2>Edit Profile</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="password-section">
                    <h3 class="password-title">Change Password</h3>
                    <p class="text-muted mb-4">Leave these fields blank if you don't want to change your password</p>
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="product.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            // If trying to change password
            if (newPassword || confirmPassword || currentPassword) {
                // Check if current password is entered
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Please enter your current password to change password');
                    return;
                }
                
                // Check if passwords match
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return;
                }
                
                // Check password length
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long');
                    return;
                }
            }
        });
    </script>
</body>
</html>