<?php
// Database connection
$servername = "127.0.0.1";
$username = "root"; // Change if you have a different username
$password = ""; // Enter your password here
$dbname = "shoe_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Function to get top selling products
function getTopProducts($conn, $start_date, $end_date, $limit = 5) {
    // Initialize variables
    $result = null;
    $data = [];
    
    $sql = "SELECT 
                p.name as product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM 
                order_items oi
            JOIN 
                products p ON oi.product_id = p.id
            JOIN 
                orders o ON oi.order_id = o.id
            WHERE 
                o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
            GROUP BY 
                p.id
            ORDER BY 
                total_quantity DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get category performance
function getCategoryPerformance($conn, $start_date, $end_date) {
    // Initialize variables
    $result = null;
    $data = [];
    
    $sql = "SELECT 
                c.name as category_name,
                COUNT(DISTINCT oi.id) as item_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM 
                order_items oi
            JOIN 
                products p ON oi.product_id = p.id
            JOIN 
                categories c ON p.category_id = c.id
            JOIN 
                orders o ON oi.order_id = o.id
            WHERE 
                o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
            GROUP BY 
                c.id
            ORDER BY 
                total_revenue DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get sales data
function getSalesData($conn, $start_date, $end_date) {
    // Initialize variables
    $result = null;
    $data = [];
    
    $sql = "SELECT 
                DATE(o.created_at) as date,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity * oi.price) as total_sales
            FROM 
                orders o
            JOIN 
                order_items oi ON o.id = oi.order_id
            WHERE 
                o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
            GROUP BY 
                DATE(o.created_at)
            ORDER BY 
                date ASC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Get appropriate data based on report type
$sales_data = [];
$top_products = [];
$category_performance = [];

if ($report_type == 'sales' || $report_type == 'all') {
    $sales_data = getSalesData($conn, $start_date, $end_date);
}

if ($report_type == 'products' || $report_type == 'all') {
    $top_products = getTopProducts($conn, $start_date, $end_date);
}

if ($report_type == 'categories' || $report_type == 'all') {
    $category_performance = getCategoryPerformance($conn, $start_date, $end_date);
}

// Calculate summary statistics
$total_sales = 0;
$total_orders = 0;
$average_order_value = 0;

if (!empty($sales_data)) {
    foreach ($sales_data as $day) {
        $total_sales += $day['total_sales'];
        $total_orders += $day['order_count'];
    }
    $average_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Store - Sales Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #8338ec;
            --accent-color: #ff006e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .navbar-brand i {
            margin-right: 8px;
        }
        
        .nav-link {
            font-weight: 500;
            margin: 0 5px;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--accent-color) !important;
        }
        
        .nav-link.active {
            border-bottom: 2px solid var(--accent-color);
        }
        
        .container {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .page-header {
            background-color: white;
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary-color);
        }
        
        .page-header h1 {
            margin-bottom: 0;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
        }
        
        .card-header h2 {
            font-size: 1.3rem;
            margin-bottom: 0;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-control {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 134, 255, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2a75f3;
            border-color: #2a75f3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 48px;
            opacity: 0.2;
        }
        
        .stat-card .stat-title {
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .table tbody tr:hover {
            background-color: rgba(58, 134, 255, 0.05);
        }
        
        .table td, .table th {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .footer {
    background-color: var(--dark-color);
    color: white;
    padding: 1px 0; /* Further reduced padding */
    text-align: center;
    position: static;
    bottom: 0;
    width: 100%;
}

        
        .report-type-tab {
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark-color);
            margin-right: 10px;
            font-weight: 500;
        }
        
        .report-type-tab.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .report-type-tab:hover:not(.active) {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints"></i> Shoe Store Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-shoe-prints"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                            <i class="fas fa-users"></i> Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar me-2"></i>Sales Reports</h1>
        </div>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-filter me-2"></i>Report Filters</h2>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type">
                            <option value="all" <?php echo $report_type == 'all' ? 'selected' : ''; ?>>All Reports</option>
                            <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales Overview</option>
                            <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>Product Performance</option>
                            <option value="categories" <?php echo $report_type == 'categories' ? 'selected' : ''; ?>>Category Analysis</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-title">Total Sales</div>
                    <div class="stat-value">$<?php echo number_format($total_sales, 2); ?></div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #8338ec, #3a86ff);">
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #ff006e, #8338ec);">
                    <div class="stat-title">Average Order Value</div>
                    <div class="stat-value">$<?php echo number_format($average_order_value, 2); ?></div>
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Types Navigation -->
        <div class="mb-4">
            <div class="d-flex">
                <a href="?report_type=all&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large me-2"></i>All Reports
                </a>
                <a href="?report_type=sales&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'sales' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i>Sales Overview
                </a>
                <a href="?report_type=products&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-shoe-prints me-2"></i>Product Performance
                </a>
                <a href="?report_type=categories&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-tags me-2"></i>Category Analysis
                </a>
            </div>
        </div>
        
        <?php if ($report_type == 'sales' || $report_type == 'all'): ?>
        <!-- Sales Over Time Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-chart-line me-2"></i>Sales Performance</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Daily Sales Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-table me-2"></i>Daily Sales Breakdown</h2>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Sales Amount</th>
                            <th>Average Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sales_data)): ?>
                            <?php foreach ($sales_data as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['order_count']; ?></td>
                                    <td>$<?php echo number_format($day['total_sales'], 2); ?></td>
                                    <td>$<?php echo number_format($day['order_count'] > 0 ? $day['total_sales'] / $day['order_count'] : 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No sales data found for the selected period</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'products' || $report_type == 'all'): ?>
        <!-- Top Products Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-trophy me-2"></i>Top Selling Products</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="productsChart"></canvas>
                </div>
                
                <div class="mt-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo $product['total_quantity']; ?></td>
                                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No product data found for the selected period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'categories' || $report_type == 'all'): ?>
        <!-- Category Performance -->
        <div class="card mb-4">
            <div class="card-header">
                <h2><i class="fas fa-tags me-2"></i>Category Performance</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
                
                <div class="mt-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Items Sold</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($category_performance)): ?>
                                <?php foreach ($category_performance as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo $category['item_count']; ?></td>
                                        <td><?php echo $category['total_quantity']; ?></td>
                                        <td>$<?php echo number_format($category['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No category data found for the selected period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Shoe Store Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Initialize charts if data exists
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (($report_type == 'sales' || $report_type == 'all') && !empty($sales_data)): ?>
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($day) { return "'" . date('M d', strtotime($day['date'])) . "'"; }, $sales_data)); ?>],
                    datasets: [{
                        label: 'Sales ($)',
                        data: [<?php echo implode(',', array_map(function($day) { return $day['total_sales']; }, $sales_data)); ?>],
                        backgroundColor: 'rgba(58, 134, 255, 0.2)',
                        borderColor: 'rgba(58, 134, 255, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true
                    }, {
                        label: 'Orders',
                        data: [<?php echo implode(',', array_map(function($day) { return $day['order_count']; }, $sales_data)); ?>],
                        backgroundColor: 'rgba(255, 0, 110, 0.2)',
                        borderColor: 'rgba(255, 0, 110, 1)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: true,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Sales ($)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Orders'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if (($report_type == 'products' || $report_type == 'all') && !empty($top_products)): ?>
            // Products Chart
            const productsCtx = document.getElementById('productsChart').getContext('2d');
            const productsChart = new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($product) { return "'" . addslashes($product['product_name']) . "'"; }, $top_products)); ?>],
                    datasets: [{
                        label: 'Units Sold',
                        data: [<?php echo implode(',', array_map(function($product) { return $product['total_quantity']; }, $top_products)); ?>],
                        backgroundColor: [
                            'rgba(58, 134, 255, 0.7)',
                            'rgba(131, 56, 236, 0.7)',
                            'rgba(255, 0, 110, 0.7)',
                            'rgba(255, 190, 11, 0.7)',
                            'rgba(75, 192, 192, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Units Sold'
                            }
                        }
                    },
                    plugins:plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Units Sold: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if (($report_type == 'categories' || $report_type == 'all') && !empty($category_performance)): ?>
            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(',', array_map(function($category) { return "'" . addslashes($category['category_name']) . "'"; }, $category_performance)); ?>],
                    datasets: [{
                        label: 'Revenue',
                        data: [<?php echo implode(',', array_map(function($category) { return $category['total_revenue']; }, $category_performance)); ?>],
                        backgroundColor: [
                            'rgba(58, 134, 255, 0.7)',
                            'rgba(131, 56, 236, 0.7)',
                            'rgba(255, 0, 110, 0.7)',
                            'rgba(255, 190, 11, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `$${value.toFixed(2)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
    </script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>