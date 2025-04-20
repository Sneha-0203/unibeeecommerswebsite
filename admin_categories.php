<?php

session_start();
require_once 'config/database.php';
// Handle form submissions
$message = "";

// Add new category
if (isset($_POST['add_category'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "New category added successfully";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Delete category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM categories WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Category deleted successfully";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Edit category
if (isset($_POST['update_category'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "UPDATE categories SET name='$name', description='$description' WHERE id=$id";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Category updated successfully";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch categories
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIBEE - Category Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0b5ed7; /* UNIBEE yellow */
            --secondary-color: #212529; /* Dark gray */
            --accent-color: #ff006e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 12px;
            --box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-left: 250px; /* Space for fixed sidebar */
        }
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
        
        .container {
            max-width: 1200px;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(135deg, white, #f8f9fa);
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            margin-bottom: 0;
            color: var(--dark-color);
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .page-header h1 i {
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            box-shadow: 0 4px 8px rgba(243, 182, 40, 0.3);
        }
        
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            padding: 15px 20px;
            margin-bottom: 30px;
        }
        
        .alert-info {
            background-color: rgba(58, 134, 255, 0.1);
            color: #2a75f3;
            border-left: 4px solid #2a75f3;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color),#0b5ed7);
            color: var(--dark-color);
            border-bottom: none;
            padding: 20px 25px;
            font-weight: bold;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-header h2 i {
            margin-right: 10px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(243, 182, 40, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--dark-color);
            box-shadow: 0 4px 8px rgba(243, 182, 40, 0.3);
        }
        
        .btn-primary:hover {
            background-color: #e6a91f;
            border-color: #e6a91f;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(243, 182, 40, 0.4);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            box-shadow: 0 4px 8px rgba(255, 0, 110, 0.3);
        }
        
        .btn-danger:hover {
            background-color: #e50063;
            border-color: #e50063;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 0, 110, 0.4);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(243, 182, 40, 0.05);
            transform: scale(1.01);
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #ffd166);
            color: var(--dark-color);
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .modal-title i {
            margin-right: 10px;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        
        /* Animation effects */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card, .page-header, .alert {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.3s; }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            body {
                padding-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .toggle-sidebar {
                display: block;
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
                            <a href="admin_products.php" class="nav-link" aria-current="page">
                                <i class="fas fa-shoe-prints me-2"></i>
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="admin_categories.php" class="nav-link active text-white">
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
                            <li><a class="dropdown-item" href="admin_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

    <div class="container">
        <!-- Toggle sidebar button (visible on mobile) -->
        <button class="btn btn-primary d-lg-none mb-3 toggle-sidebar" type="button">
            <i class="fas fa-bars"></i>
        </button>
    
        <div class="page-header">
            <h1><i class="fas fa-tags"></i>Category Management</h1>
            <span class="badge bg-primary rounded-pill fs-6"><?php echo $result->num_rows; ?> Categories</span>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info message">
                <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Add Category Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i>Add New Category</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter category name">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" id="description" name="description" rows="1" placeholder="Enter category description"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-save"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-list"></i>Categories</h2>
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" class="form-control" id="categorySearch" placeholder="Search categories...">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                <th><i class="fas fa-tag me-1"></i>Name</th>
                                <th><i class="fas fa-align-left me-1"></i>Description</th>
                                <th class="text-end"><i class="fas fa-cogs me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row["id"]; ?></td>
                                        <td>
                                            <span class="fw-medium"><?php echo htmlspecialchars($row["name"]); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $description = htmlspecialchars($row["description"]);
                                            echo !empty($description) ? $description : '<span class="text-muted fst-italic">No description</span>';
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $row["id"]; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?delete=<?php echo $row["id"]; ?>" 
                                               class="btn btn-sm btn-danger ms-1"
                                               onclick="return confirm('Are you sure you want to delete this category?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $row["id"]; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="id" value="<?php echo $row["id"]; ?>">
                                                        <div class="mb-3">
                                                            <label for="edit_name<?php echo $row["id"]; ?>" class="form-label">Category Name</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                                <input type="text" class="form-control" 
                                                                       id="edit_name<?php echo $row["id"]; ?>" 
                                                                       name="name" 
                                                                       value="<?php echo htmlspecialchars($row["name"]); ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_description<?php echo $row["id"]; ?>" class="form-label">Description</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                                                <textarea class="form-control" 
                                                                          id="edit_description<?php echo $row["id"]; ?>" 
                                                                          name="description" 
                                                                          rows="3"><?php echo htmlspecialchars($row["description"]); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-2"></i>Cancel
                                                            </button>
                                                            <button type="submit" name="update_category" class="btn btn-primary">
                                                                <i class="fas fa-save me-2"></i>Update Category
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="py-5">
                                            <i class="fas fa-tag fa-4x text-muted mb-3"></i>
                                            <h5>No categories found</h5>
                                            <p class="text-muted">Start by adding your first category above.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Category search functionality
        document.getElementById('categorySearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const description = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchValue) || description.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Mobile sidebar toggle
        document.querySelector('.toggle-sidebar')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>