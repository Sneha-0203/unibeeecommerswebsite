<?php
session_start();

// Include database connection
require_once 'config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['id'];
$success_message = "";
$error_message = "";

// Fetch current admin data
$stmt = $conn->prepare("SELECT id, username, email, name, created_at FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin_data = $result->fetch_assoc();
} else {
    $error_message = "Error: Admin data not found.";
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Update admin information
    $update_stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $name, $email, $admin_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $success_message = "Profile updated successfully!";
        
        // Refresh admin data
        $stmt->execute();
        $result = $stmt->get_result();
        $admin_data = $result->fetch_assoc();
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
    
    $update_stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Verify current password
        $pwd_stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        
        // Check if prepare statement was successful
        if ($pwd_stmt === false) {
            $error_message = "Error preparing statement: " . $conn->error;
        } else {
            $pwd_stmt->bind_param("i", $admin_id);
            $pwd_stmt->execute();
            $pwd_result = $pwd_stmt->get_result();
            $pwd_row = $pwd_result->fetch_assoc();
            
            if (password_verify($current_password, $pwd_row['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $pwd_update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $pwd_update_stmt->bind_param("si", $hashed_password, $admin_id);
                
                if ($pwd_update_stmt->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password: " . $conn->error;
                }
                
                $pwd_update_stmt->close();
            } else {
                $error_message = "Current password is incorrect.";
            }
            
            $pwd_stmt->close();
        }
    }
}

// We don't have admin_activity_log table, so we'll set this to null
$activity_result = null;

// Close the database connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - UNIBEE</title>
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
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 48px;
            margin-right: 20px;
        }
        .activity-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
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
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2 fs-5"></i>
                            <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : htmlspecialchars($_SESSION['username']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item active" href="admin_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2 class="mb-4">Admin Profile</h2>
                
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
                
                <!-- Profile Header -->
                <div class="profile-header shadow-sm d-flex align-items-center">
                    <div class="profile-image">
                        <?php echo substr(isset($admin_data['name']) ? $admin_data['name'] : $admin_data['username'], 0, 1); ?>
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($admin_data['name']); ?></h3>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($admin_data['email']); ?></p>
                        <p class="mb-1"><i class="fas fa-user me-2"></i>Administrator</p>
                        <p class="text-muted"><i class="fas fa-clock me-2"></i>Member since <?php echo date('F j, Y', strtotime($admin_data['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Update Profile Form -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" disabled>
                                        <div class="form-text text-muted">Username cannot be changed</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters long</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="two_factor_auth" checked disabled>
                                    <label class="form-check-label" for="two_factor_auth">Two-factor authentication</label>
                                    <div class="form-text text-muted">Coming soon</div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="login_alerts" checked>
                                    <label class="form-check-label" for="login_alerts">Email alerts for new logins</label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="activity_log" checked>
                                    <label class="form-check-label" for="activity_log">Enable activity logging</label>
                                </div>
                                
                                <button type="button" class="btn btn-secondary">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Session Info -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Current Session</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-globe me-2 text-primary"></i>
                                        <strong>IP Address:</strong>
                                    </div>
                                    <p class="text-muted ms-4"><?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-clock me-2 text-primary"></i>
                                        <strong>Last Activity:</strong>
                                    </div>
                                    <p class="text-muted ms-4"><?php echo isset($_SESSION['last_activity']) ? date('F j, Y H:i:s', $_SESSION['last_activity']) : date('F j, Y H:i:s'); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-browser me-2 text-primary"></i>
                                        <strong>Browser:</strong>
                                    </div>
                                    <p class="text-muted ms-4"><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></p>
                                </div>
                                
                                <a href="logout.php" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>End Session
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>