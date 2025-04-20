<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_users.php');
    exit();
}

// Get user ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_users.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Fetch user details
$user_query = "SELECT id, name, email, created_at FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    // User not found
    header('Location: admin_users.php');
    exit();
}

$user = $user_result->fetch_assoc();

// Get login history - This would normally be in a separate table, but for this example we'll create sample data
// In a real application, you would have a table tracking login events
$login_history = [
    ['date' => date('Y-m-d H:i:s', strtotime('-1 day')), 'ip' => '192.168.1.' . rand(1, 255), 'device' => 'Desktop - Chrome'],
    ['date' => date('Y-m-d H:i:s', strtotime('-3 days')), 'ip' => '192.168.1.' . rand(1, 255), 'device' => 'Mobile - Safari'],
    ['date' => date('Y-m-d H:i:s', strtotime('-5 days')), 'ip' => '192.168.1.' . rand(1, 255), 'device' => 'Desktop - Firefox'],
];

// Get recent activities - Also would be in a separate table in a real app
$activities = [
    ['date' => date('Y-m-d H:i:s', strtotime('-2 days')), 'action' => 'Updated profile'],
    ['date' => date('Y-m-d H:i:s', strtotime('-4 days')), 'action' => 'Changed password'],
    ['date' => date('Y-m-d H:i:s', strtotime('-7 days')), 'action' => 'Account created'],
];

// Calculate account age
$created_date = new DateTime($user['created_at']);
$current_date = new DateTime();
$account_age = $created_date->diff($current_date);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Shoe Store Admin</title>
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
        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 36px;
            color: #495057;
            margin-bottom: 15px;
        }
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: #dee2e6;
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -36px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
        .card-hover:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: box-shadow 0.3s ease-in-out;
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
                            <a href="admin_users.php" class="nav-link active" aria-current="page">
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
                            <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></strong>
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
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="admin_users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">User Details</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>User Details</h2>
                    <div>
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i> Edit User
                        </a>
                        <button type="button" class="btn btn-danger delete-user" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['name']); ?>">
                            <i class="fas fa-trash me-1"></i> Delete User
                        </button>
                    </div>
                </div>

                <!-- User Profile Card -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow h-100">
                            <div class="card-body text-center">
                                <div class="user-avatar-large mx-auto">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p class="card-text">
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-calendar me-2"></i>
                                    Member since: <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    Account age: 
                                    <?php 
                                    if ($account_age->y > 0) {
                                        echo $account_age->y . ' year(s), ';
                                    }
                                    echo $account_age->m . ' month(s), ' . $account_age->d . ' day(s)';
                                    ?>
                                </p>
                                <div class="d-grid gap-2 mt-4">
                                    <button class="btn btn-outline-primary" type="button">
                                        <i class="fas fa-envelope me-1"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="row h-100">
                            <!-- Account Activity -->
                            <div class="col-md-12 mb-4">
                                <div class="card shadow h-100 card-hover">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Account Activity</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="timeline">
                                            <?php foreach ($activities as $activity): ?>
                                                <div class="timeline-item">
                                                    <div class="d-flex justify-content-between">
                                                        <strong><?php echo $activity['action']; ?></strong>
                                                        <span class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Login History -->
                            <div class="col-md-12">
                                <div class="card shadow h-100 card-hover">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Login History</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>IP Address</th>
                                                        <th>Device/Browser</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($login_history as $login): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y H:i', strtotime($login['date'])); ?></td>
                                                            <td><?php echo $login['ip']; ?></td>
                                                            <td><?php echo $login['device']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="row">
                    <!-- User Orders Summary -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow h-100 card-hover">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Orders Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h3 class="fw-bold">0</h3>
                                        <p class="text-muted mb-0">Total Orders</p>
                                    </div>
                                    <div class="col-4">
                                        <h3 class="fw-bold">$0</h3>
                                        <p class="text-muted mb-0">Total Spent</p>
                                    </div>
                                    <div class="col-4">
                                        <h3 class="fw-bold">0</h3>
                                        <p class="text-muted mb-0">Products Bought</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <a href="admin_orders.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        View All Orders <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Notes -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow h-100 card-hover">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Admin Notes</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <textarea class="form-control" id="userNotes" rows="5" placeholder="Add notes about this user..."></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Notes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="delete-user-name"></span>"?</p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Delete User</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle delete user button click
            $('.delete-user').click(function() {
                var userId = $(this).data('id');
                var userName = $(this).data('name');
                
                $('#delete-user-name').text(userName);
                $('#confirm-delete').attr('href', 'admin_users.php?action=delete&id=' + userId + '&confirm=true');
                $('#deleteUserModal').modal('show');
            });
        });
    </script>
</body>
</html>