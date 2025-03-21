<?php
session_start();
require_once 'config/database.php';
// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_users.php');
    exit();
}

// Simplified user management - remove status checks since we don't have that column
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'delete' && isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
        // Just delete the user - we don't have order checks in our simplified version
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $success_message = "User deleted successfully.";
    }
}

// Search parameter (remove status filter)
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Simplified WHERE clause - only use search
$where_clause = "WHERE 1=1";

if (!empty($search_term)) {
    $search_term = mysqli_real_escape_string($conn, $search_term);
    $where_clause .= " AND (name LIKE '%$search_term%' OR email LIKE '%$search_term%')";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total number of users - using error checking
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = $conn->query($count_query);

// Add error checking here
if (!$count_result) {
    die("Query error: " . $conn->error);
}

$count_row = $count_result->fetch_assoc();
$total_users = $count_row['total'];
$total_pages = ceil($total_users / $per_page);

// Fetch users with pagination - simplified to use available columns
$users_query = "SELECT id, name, email, created_at FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param("ii", $per_page, $offset);
$users_stmt->execute();
$users_result = $users_stmt->get_result();

// Simplified statistics - just count total users
$active_users = $total_users; // Since we don't have status column, all users are considered active
$inactive_users = 0;
$admin_users = 0;

// Get recent user registrations
$recent_registrations_query = "SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$recent_registrations_result = $conn->query($recent_registrations_query);

// We're skipping top customers section since we don't have orders table
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Shoe Store Admin</title>
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
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #495057;
        }
        .table-responsive {
            overflow-x: auto;
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
                <h2 class="mb-4">Manage Users</h2>
                
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
                
                <!-- Stats Cards - Simplified -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Users</h5>
                                        <p class="card-text fs-2 mb-0"><?php echo $total_users; ?></p>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                                <small class="text-white">All registered accounts</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Registered Today</h5>
                                        <p class="card-text fs-2 mb-0">
                                            <?php
                                            $today = date('Y-m-d');
                                            $today_query = "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = '$today'";
                                            $today_result = $conn->query($today_query);
                                            $today_count = $today_result->fetch_assoc()['count'];
                                            echo $today_count;
                                            ?>
                                        </p>
                                    </div>
                                    <i class="fas fa-user-plus fa-3x opacity-50"></i>
                                </div>
                                <small class="text-white">New registrations today</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">This Month</h5>
                                        <p class="card-text fs-2 mb-0">
                                            <?php
                                            $month_start = date('Y-m-01');
                                            $month_query = "SELECT COUNT(*) as count FROM users WHERE created_at >= '$month_start'";
                                            $month_result = $conn->query($month_query);
                                            $month_count = $month_result->fetch_assoc()['count'];
                                            echo $month_count;
                                            ?>
                                        </p>
                                    </div>
                                    <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                                </div>
                                <small class="text-white">Users registered this month</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Management Tools -->
                <div class="row mb-4">
                    <!-- Search and Filter - Simplified -->
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">User Management</h5>
                                <a href="add_user.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Add New User
                                </a>
                            </div>
                            <div class="card-body">
                                <form action="" method="get" class="mb-4">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" name="search" placeholder="Search users by name or email" value="<?php echo htmlspecialchars($search_term); ?>">
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter me-1"></i> Search
                                            </button>
                                        </div>
                                        <div class="col-auto">
                                            <a href="admin_users.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-sync-alt me-1"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($users_result && $users_result->num_rows > 0): ?>
                                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $user['id']; ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="user-avatar me-2">
                                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                                </div>
                                                                <?php echo htmlspecialchars($user['name']); ?>
                                                                </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary" title="Edit User">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-info" title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-danger delete-user" title="Delete User" 
                                                                        data-id="<?php echo $user['id']; ?>" 
                                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No users found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="User pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
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
                
                <!-- Recent Registrations - Only section we can keep -->
                <div class="row">
                    <!-- Recent Registrations -->
                    <div class="col-md-12 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Registrations</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_registrations_result && $recent_registrations_result->num_rows > 0): ?>
                                                <?php while ($user = $recent_registrations_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="user-avatar me-2">
                                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                                </div>
                                                                <?php echo htmlspecialchars($user['name']); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No recent registrations</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>