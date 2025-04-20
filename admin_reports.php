<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Handle date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';
$export = isset($_GET['export']) && $_GET['export'] === 'excel';

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

// Excel Export Functionality
if ($export) {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="UNIBEE_Report_' . $report_type . '_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create the Excel file content
    echo "<!DOCTYPE html>";
    echo "<html>";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "</head>";
    echo "<body>";
    
    // Summary Section
    echo "<table border='1'>";
    echo "<tr><th colspan='3'>UNIBEE Sales Report</th></tr>";
    echo "<tr><th colspan='3'>Period: $start_date to $end_date</th></tr>";
    echo "<tr><th>Total Sales</th><th>Total Orders</th><th>Average Order Value</th></tr>";
    echo "<tr><td>$" . number_format($total_sales, 2) . "</td><td>" . number_format($total_orders) . "</td><td>$" . number_format($average_order_value, 2) . "</td></tr>";
    echo "</table>";
    echo "<br>";
    
    // Sales Data
    if ($report_type == 'sales' || $report_type == 'all') {
        echo "<table border='1'>";
        echo "<tr><th colspan='4'>Daily Sales Breakdown</th></tr>";
        echo "<tr><th>Date</th><th>Orders</th><th>Sales Amount</th><th>Average Order Value</th></tr>";
        
        if (!empty($sales_data)) {
            foreach ($sales_data as $day) {
                $daily_avg = $day['order_count'] > 0 ? $day['total_sales'] / $day['order_count'] : 0;
                echo "<tr>";
                echo "<td>" . date('M d, Y', strtotime($day['date'])) . "</td>";
                echo "<td>" . $day['order_count'] . "</td>";
                echo "<td>$" . number_format($day['total_sales'], 2) . "</td>";
                echo "<td>$" . number_format($daily_avg, 2) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No sales data found for the selected period</td></tr>";
        }
        
        echo "</table>";
        echo "<br>";
    }
    
    // Product Data
    if ($report_type == 'products' || $report_type == 'all') {
        echo "<table border='1'>";
        echo "<tr><th colspan='3'>Top Selling Products</th></tr>";
        echo "<tr><th>Product Name</th><th>Units Sold</th><th>Revenue</th></tr>";
        
        if (!empty($top_products)) {
            foreach ($top_products as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                echo "<td>" . $product['total_quantity'] . "</td>";
                echo "<td>$" . number_format($product['total_revenue'], 2) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No product data found for the selected period</td></tr>";
        }
        
        echo "</table>";
        echo "<br>";
    }
    
    // Category Data
    if ($report_type == 'categories' || $report_type == 'all') {
        echo "<table border='1'>";
        echo "<tr><th colspan='4'>Category Performance</th></tr>";
        echo "<tr><th>Category</th><th>Items Sold</th><th>Units Sold</th><th>Revenue</th></tr>";
        
        if (!empty($category_performance)) {
            foreach ($category_performance as $category) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($category['category_name']) . "</td>";
                echo "<td>" . $category['item_count'] . "</td>";
                echo "<td>" . $category['total_quantity'] . "</td>";
                echo "<td>$" . number_format($category['total_revenue'], 2) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No category data found for the selected period</td></tr>";
        }
        
        echo "</table>";
    }
    
    echo "</body>";
    echo "</html>";
    
    exit; // Stop further processing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNIBEE - Sales Reports</title>
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
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --info-color: #00bcd4;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-left: 250px; /* Make space for fixed sidebar */
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
            max-width: 1400px;
            margin: 30px auto 50px auto;
            padding: 0 25px;
        }
        
        .page-header {
            background: linear-gradient(135deg, white, #f8f9fa);
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }
        
        .page-header:before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 100%;
            background: linear-gradient(135deg, transparent, rgba(58, 134, 255, 0.1));
        }
        
        .page-header h1 {
            margin-bottom: 0;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
            padding: 18px 20px;
            position: relative;
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
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            font-size: 0.95rem;
            box-shadow: none;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(58, 134, 255, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1e70e7);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e70e7, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #388e3c);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #388e3c, var(--success-color));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 60px;
            opacity: 0.2;
        }
        
        .stat-card .stat-title {
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
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
            padding: 15px;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(58, 134, 255, 0.05);
            transform: scale(1.01);
        }
        
        .table td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 15px 0;
            text-align: center;
            width: 100%;
            margin-top: 50px;
        }
        
        .report-type-tab {
            cursor: pointer;
            padding: 12px 18px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark-color);
            margin-right: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            border: 1px solid transparent;
        }
        
        .report-type-tab.active {
            background: linear-gradient(135deg, var(--primary-color), #1e70e7);
            color: white;
            box-shadow: 0 4px 10px rgba(58, 134, 255, 0.3);
        }
        
        .report-type-tab:hover:not(.active) {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
        }
        
        .report-type-tab i {
            margin-right: 8px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #a9a9a9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #888;
        }
        
        /* Date range styles */
        .date-range-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            body {
                padding-left: 0;
            }
            
            .sidebar {
                display: none;
            }
            
            .stat-card .stat-value {
                font-size: 1.5rem;
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
                            <a href="admin_reports.php" class="nav-link active text-white">
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
                <div class="page-header">
                    <h1><i class="fas fa-chart-bar me-2"></i>Sales Reports</h1>
                </div>
                
                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h2><i class="fas fa-filter me-2"></i>Report Filters</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="" class="row g-3" id="reportForm">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label date-range-label">Start Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label date-range-label">End Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="report_type" class="form-label date-range-label">Report Type</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                    <select class="form-select" id="report_type" name="report_type">
                                        <option value="all" <?php echo $report_type == 'all' ? 'selected' : ''; ?>>All Reports</option>
                                        <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales Overview</option>
                                        <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>Product Performance</option>
                                        <option value="categories" <?php echo $report_type == 'categories' ? 'selected' : ''; ?>>Category Analysis</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                
                                <button type="submit" name="export" value="excel" class="btn btn-success">
        <i class="fas fa-file-excel me-2"></i>Export to Excel
    </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-title">Total Sales</div>
                            <div class="stat-value">$<?php echo number_format($total_sales, 2); ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #8338ec, #3a86ff);">
                            <div class="stat-title">Total Orders</div>
                            <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
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
                    <div class="d-flex flex-wrap">
                        <a href="?report_type=all&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i>All Reports
                        </a>
                        <a href="?report_type=sales&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'sales' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>Sales Overview
                        </a>
                        <a href="?report_type=products&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'products' ? 'active' : ''; ?>">
                            <i class="fas fa-shoe-prints"></i>Product Performance
                        </a>
                        <a href="?report_type=categories&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="report-type-tab <?php echo $report_type == 'categories' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i>Category Analysis
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
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                            <div class="table-responsive">
                                <table class="table table-hover">
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