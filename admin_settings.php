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

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get store settings from database
function getStoreSettings($conn) {
    $settings = array(
        'store_name' => 'Shoe Store',
        'store_email' => 'contact@shoestore.com',
        'store_phone' => '+91 98765 43210',
        'store_address' => '123 Fashion Street, Mumbai, Maharashtra 400001',
        'currency' => 'INR',
        'tax_rate' => 18.0, // GST rate in India
        'shipping_fee' => 150.00,
        'free_shipping_threshold' => 2000.00,
        'timezone' => 'Asia/Kolkata',
        'enable_customer_reviews' => 1,
        'enable_inventory_tracking' => 1,
        'low_stock_threshold' => 5,
        'site_logo' => 'assets/img/logo.png',
        'site_theme' => 'default',
        'show_out_of_stock' => 1,
        'email_notifications' => 1
    );
    
    // In a real application, you would fetch these from a database
    // $sql = "SELECT * FROM store_settings LIMIT 1";
    // $result = $conn->query($sql);
    // if ($result && $result->num_rows > 0) {
    //    $settings = $result->fetch_assoc();
    // }
    
    return $settings;
}

// Get payment methods
function getPaymentMethods($conn) {
    $methods = array(
        array('id' => 1, 'name' => 'Credit Card', 'enabled' => 1),
        array('id' => 2, 'name' => 'PayPal', 'enabled' => 1),
        array('id' => 3, 'name' => 'Phone Pay', 'enabled' => 0),
        array('id' => 4, 'name' => 'Google Pay', 'enabled' => 0),
        array('id' => 5, 'name' => 'Bank Transfer', 'enabled' => 1)
    );
    
    // In a real application, you would fetch these from a database
    // $sql = "SELECT * FROM payment_methods";
    // $result = $conn->query($sql);
    // if ($result && $result->num_rows > 0) {
    //    while($row = $result->fetch_assoc()) {
    //        $methods[] = $row;
    //    }
    // }
    
    return $methods;
}

// Get admin users
function getAdminUsers($conn) {
    $users = array(
        array('id' => 1, 'username' => 'admin', 'email' => 'admin@shoestore.com', 'role' => 'Administrator', 'last_login' => '2025-03-21 14:32:45'),
        array('id' => 2, 'username' => 'manager', 'email' => 'manager@shoestore.com', 'role' => 'Manager', 'last_login' => '2025-03-20 09:15:22'),
        array('id' => 3, 'username' => 'staff', 'email' => 'staff@shoestore.com', 'role' => 'Staff', 'last_login' => '2025-03-19 16:45:10')
    );
    
    // In a real application, you would fetch these from a database
    // $sql = "SELECT * FROM admin_users";
    // $result = $conn->query($sql);
    // if ($result && $result->num_rows > 0) {
    //    while($row = $result->fetch_assoc()) {
    //        $users[] = $row;
    //    }
    // }
    
    return $users;
}

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_store_settings'])) {
        // In a real application, you would update the database
        // $sql = "UPDATE store_settings SET store_name = '...', store_email = '...', ...";
        // if ($conn->query($sql) === TRUE) {
        $message = "Store settings updated successfully!";
        $message_type = "success";
        // } else {
        //    $message = "Error updating store settings: " . $conn->error;
        //    $message_type = "danger";
        // }
    } elseif (isset($_POST['update_payment_methods'])) {
        // Process payment methods update
        $message = "Payment methods updated successfully!";
        $message_type = "success";
    } elseif (isset($_POST['add_admin_user'])) {
        // Process add new admin user
        $message = "New admin user added successfully!";
        $message_type = "success";
    } elseif (isset($_POST['update_password'])) {
        // Process password update
        $message = "Password updated successfully!";
        $message_type = "success";
    }
}

// Get settings data
$store_settings = getStoreSettings($conn);
$payment_methods = getPaymentMethods($conn);
$admin_users = getAdminUsers($conn);

// Available timezones
$timezones = array(
    'Asia/Kolkata' => 'India Standard Time (IST) - New Delhi, Mumbai, Kolkata',
    'Asia/Calcutta' => 'India Standard Time (IST) - Alternative'
);

// Available themes
$themes = array(
    'default' => 'Default (Blue)',
    'dark' => 'Dark Mode',
    'light' => 'Light Mode',
    'green' => 'Green Theme',
    'purple' => 'Purple Theme'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Store - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            margin-bottom: 100px;
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
        
        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        
        .form-control:focus, .form-select:focus {
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
        
        .btn-success {
            background-color: #0ead69;
            border-color: #0ead69;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            background-color: #0c9b5a;
            border-color: #0c9b5a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-danger {
            background-color: #ff006e;
            border-color: #ff006e;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background-color: #e50063;
            border-color: #e50063;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 1px 0;
            text-align: center;
            position: static;
            bottom: 0;
            width: 100%;
        }
        
        .settings-tab {
            cursor: pointer;
            padding: 15px 20px;
            border-radius: 10px;
            transition: all 0.3s;
            margin-bottom: 10px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .settings-tab.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }
        
        .settings-tab:hover:not(.active) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .settings-tab i {
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .theme-preview {
            width: 100%;
            height: 100px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .theme-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .theme-preview.default {
            background: linear-gradient(135deg, #3a86ff, #8338ec);
        }
        
        .theme-preview.dark {
            background: linear-gradient(135deg, #212529, #343a40);
        }
        
        .theme-preview.light {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            color: #212529;
        }
        
        .theme-preview.green {
            background: linear-gradient(135deg, #0ead69, #38b000);
        }
        
        .theme-preview.purple {
            background: linear-gradient(135deg, #8338ec, #c77dff);
        }
        
        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
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
            <h1><i class="fas fa-cog me-2"></i>Store Settings</h1>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-md-3">
                <div class="settings-tab active" data-target="store-settings">
                    <i class="fas fa-store me-2"></i> Store Information
                </div>
                <div class="settings-tab" data-target="payment-settings">
                    <i class="fas fa-credit-card me-2"></i> Payment Methods
                </div>
                <div class="settings-tab" data-target="appearance-settings">
                    <i class="fas fa-palette me-2"></i> Appearance
                </div>
                <div class="settings-tab" data-target="notification-settings">
                    <i class="fas fa-bell me-2"></i> Notifications
                </div>
                <div class="settings-tab" data-target="user-settings">
                    <i class="fas fa-users-cog me-2"></i> User Management
                </div>
                <div class="settings-tab" data-target="security-settings">
                    <i class="fas fa-shield-alt me-2"></i> Security
                </div>
                <div class="settings-tab" data-target="backup-settings">
                    <i class="fas fa-database me-2"></i> Backup & Restore
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-md-9">
                <!-- Store Information Settings -->
                <div class="settings-content" id="store-settings">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-store me-2"></i>Store Information</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="store_name" class="form-label">Store Name</label>
                                        <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($store_settings['store_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="store_email" class="form-label">Store Email</label>
                                        <input type="email" class="form-control" id="store_email" name="store_email" value="<?php echo htmlspecialchars($store_settings['store_email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="store_phone" class="form-label">Store Phone</label>
                                        <input type="text" class="form-control" id="store_phone" name="store_phone" value="<?php echo htmlspecialchars($store_settings['store_phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="currency" class="form-label">Currency</label>
                                        <input type="text" class="form-control" id="store_phone" name="store_phone" value="<?php echo htmlspecialchars($store_settings['store_phone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="currency" class="form-label">Currency</label>
                                        <select class="form-select" id="currency" name="currency">
    <option value="INR" <?php echo $store_settings['currency'] == 'INR' ? 'selected' : ''; ?>>Indian Rupee (INR)</option>
</select>
                                    </div>
                                </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="store_address" class="form-label">Store Address</label>
                                    <textarea class="form-control" id="store_address" name="store_address" rows="2"><?php echo htmlspecialchars($store_settings['store_address']); ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" value="<?php echo $store_settings['tax_rate']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="shipping_fee" class="form-label">Default Shipping Fee</label>
                                        <input type="number" class="form-control" id="shipping_fee" name="shipping_fee" step="0.01" min="0" value="<?php echo $store_settings['shipping_fee']; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                                        <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" step="0.01" min="0" value="<?php echo $store_settings['free_shipping_threshold']; ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <?php foreach ($timezones as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo $store_settings['timezone'] == $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                        <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" min="1" value="<?php echo $store_settings['low_stock_threshold']; ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_customer_reviews" name="enable_customer_reviews" <?php echo $store_settings['enable_customer_reviews'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_customer_reviews">Enable Customer Reviews</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_inventory_tracking" name="enable_inventory_tracking" <?php echo $store_settings['enable_inventory_tracking'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_inventory_tracking">Enable Inventory Tracking</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="show_out_of_stock" name="show_out_of_stock" <?php echo $store_settings['show_out_of_stock'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_out_of_stock">Show Out of Stock Products</label>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_store_settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Store Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Settings -->
                <div class="settings-content" id="payment-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-credit-card me-2"></i>Payment Methods</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_methods as $method): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($method['name']); ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="payment_method_status[<?php echo $method['id']; ?>]" <?php echo $method['enabled'] ? 'checked' : ''; ?>>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#configurePayment<?php echo $method['id']; ?>">
                                                        <i class="fas fa-cog"></i> Configure
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_payment_methods" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Payment Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Payment Configuration Modals -->
                    <?php foreach ($payment_methods as $method): ?>
                        <div class="modal fade" id="configurePayment<?php echo $method['id']; ?>" tabindex="-1" aria-labelledby="configurePaymentLabel<?php echo $method['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="configurePaymentLabel<?php echo $method['id']; ?>">Configure <?php echo htmlspecialchars($method['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form>
                                            <?php if ($method['name'] == 'Credit Card'): ?>
                                                <div class="mb-3">
                                                    <label class="form-labelI'll complete the code for the Credit Card configuration form that appears inside the modal. Here's the rest of the code:

```php
<div class="mb-3">
    <label class="form-label">Payment Processor</label>
    <select class="form-select">
        <option>Stripe</option>
        <option>PayPal Commerce</option>
        <option>Authorize.net</option>
        <option>Square</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">API Key</label>
    <input type="password" class="form-control" placeholder="Enter API key">
</div>
<div class="mb-3">
    <label class="form-label">Secret Key</label>
    <input type="password" class="form-control" placeholder="Enter secret key">
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" checked>
    <label class="form-check-label">
        Enable Test Mode
    </label>
</div>
<?php elseif ($method['name'] == 'PayPal'): ?>
<div class="mb-3">
    <label class="form-label">PayPal Email</label>
    <input type="email" class="form-control" placeholder="Enter PayPal email">
</div>
<div class="mb-3">
    <label class="form-label">Client ID</label>
    <input type="text" class="form-control" placeholder="Enter client ID">
</div>
<div class="mb-3">
    <label class="form-label">Client Secret</label>
    <input type="password" class="form-control" placeholder="Enter client secret">
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" checked>
    <label class="form-check-label">
        Enable Sandbox Mode
    </label>
</div>
<?php elseif ($method['name'] == 'Apple Pay' || $method['name'] == 'Google Pay'): ?>
<div class="mb-3">
    <label class="form-label">Merchant ID</label>
    <input type="text" class="form-control" placeholder="Enter merchant ID">
</div>
<div class="mb-3">
    <label class="form-label">Processing Provider</label>
    <select class="form-select">
        <option>Stripe</option>
        <option>Braintree</option>
        <option>Adyen</option>
    </select>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox">
    <label class="form-check-label">
        Enable Test Mode
    </label>
</div>
<?php elseif ($method['name'] == 'Bank Transfer'): ?>
<div class="mb-3">
    <label class="form-label">Account Name</label>
    <input type="text" class="form-control" placeholder="Enter account name">
</div>
<div class="mb-3">
    <label class="form-label">Account Number</label>
    <input type="text" class="form-control" placeholder="Enter account number">
</div>
<div class="mb-3">
    <label class="form-label">Bank Name</label>
    <input type="text" class="form-control" placeholder="Enter bank name">
</div>
<div class="mb-3">
    <label class="form-label">Routing Number</label>
    <input type="text" class="form-control" placeholder="Enter routing number">
</div>
<div class="mb-3">
    <label class="form-label">Payment Instructions</label>
    <textarea class="form-control" rows="3" placeholder="Enter payment instructions to display to customers"></textarea>
</div>
<?php endif; ?>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Appearance Settings -->
                <div class="settings-content" id="appearance-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-palette me-2"></i>Store Appearance</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="site_logo" class="form-label">Store Logo</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?php echo $store_settings['site_logo']; ?>" alt="Store Logo" class="me-3" style="max-height: 50px;">
                                        <button type="button" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <input type="file" class="form-control" id="site_logo" name="site_logo">
                                    <small class="text-muted">Recommended size: 300x100 pixels, transparent PNG</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Store Theme</label>
                                    <div class="row">
                                        <?php foreach ($themes as $value => $label): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="theme-preview <?php echo $value; ?> <?php echo $store_settings['site_theme'] == $value ? 'border border-3 border-primary' : ''; ?>">
                                                    <?php echo $label; ?>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="site_theme" id="theme_<?php echo $value; ?>" value="<?php echo $value; ?>" <?php echo $store_settings['site_theme'] == $value ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="theme_<?php echo $value; ?>">
                                                        <?php echo $label; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Homepage Layout</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="homepage_layout" id="layout_grid" value="grid" checked>
                                                <label class="form-check-label" for="layout_grid">
                                                    Grid Layout
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="homepage_layout" id="layout_list" value="list">
                                                <label class="form-check-label" for="layout_list">
                                                    List Layout
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="homepage_layout" id="layout_featured" value="featured">
                                                <label class="form-check-label" for="layout_featured">
                                                    Featured Layout
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_appearance" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Appearance Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="settings-content" id="notification-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-bell me-2"></i>Notification Settings</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label class="form-label">Email Notifications</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_new_order" name="notify_new_order" checked>
                                        <label class="form-check-label" for="notify_new_order">
                                            New Order Notification
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_low_stock" name="notify_low_stock" checked>
                                        <label class="form-check-label" for="notify_low_stock">
                                            Low Stock Alert
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_new_customer" name="notify_new_customer" checked>
                                        <label class="form-check-label" for="notify_new_customer">
                                            New Customer Registration
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_product_review" name="notify_product_review" checked>
                                        <label class="form-check-label" for="notify_product_review">
                                            New Product Review
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notification_email" class="form-label">Send Notifications To</label>
                                    <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?php echo htmlspecialchars($store_settings['store_email']); ?>">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_notifications" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Notification Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- User Management -->
                <div class="settings-content" id="user-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-users-cog me-2"></i>User Management</h2>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-user-plus"></i> Add New User
                            </button>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td><?php echo htmlspecialchars($user['last_login']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary me-1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Add User Modal -->
                    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="new_username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="new_username" name="new_username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_role" class="form-label">Role</label>
                                            <select class="form-select" id="new_role" name="new_role">
                                                <option value="Administrator">Administrator</option>
                                                <option value="Manager">Manager</option>
                                                <option value="Staff">Staff</option>
                                            </select>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="add_admin_user" class="btn btn-success">Add User</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-content" id="security-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-shield-alt me-2"></i>Security Settings</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <h5 class="mb-3">Change Password</h5>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                            <span class="input-group-text password-toggle"><i class="fas fa-eye"></i></span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                            <span class="input-group-text password-toggle"><i class="fas fa-eye"></i></span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                            <span class="input-group-text password-toggle"><i class="fas fa-eye"></i></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h5 class="mb-3">Security Options</h5>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa">
                                        <label class="form-check-label" for="enable_2fa">
                                            Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="force_password_change" name="force_password_change">
                                        <label class="form-check-label" for="force_password_change">
                                            Force Password Change Every 90 Days
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="log_failed_logins" name="log_failed_logins" checked>
                                        <label class="form-check-label" for="log_failed_logins">
                                            Log Failed Login Attempts
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Security Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Restore Settings -->
                <div class="settings-content" id="backup-settings" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-database me-2"></i>Backup & Restore</h2>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h5>Database Backup</h5>
                                <p>Create a backup of your database. This backup includes all products, orders, customers, and settings.</p>
                                <button type="button" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Backup Database
                                </button>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Scheduled Backups</h5>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="enable_scheduled_backup" checked>
                                    <label class="form-check-label" for="enable_scheduled_backup">
                                        Enable Scheduled Backups
                                    </label>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Backup Frequency</label>
                                        <select class="form-select">
                                            <option>Daily</option>
                                            <option selected>Weekly</option>
                                            <option>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Keep Backups For</label>
                                        <select class="form-select">
                                            <option>7 days</option>
                                            <option>14 days</option>
                                            <option selected>30 days</option>
                                            <option>90 days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Restore Database</h5>
                                <p class="text-danger">Warning: Restoring a database will overwrite all current data. This action cannot be undone.</p>
                                <div class="mb-3">
                                    <label for="restore_file" class="form-label">Select Backup File</label>
                                    <input type="file" class="form-control" id="restore_file">
                                </div>
                                <button type="button" class="btn btn-danger">
                                    <i class="fas fa-upload me-2"></i>Restore Database
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Shoe Store Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Settings Tab Navigation
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.settings-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all settings content
                document.querySelectorAll('.settings-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Show the corresponding content
                document.getElementById(this.dataset.target).style.display = 'block';
            });
        });
        
        // Password toggle visibility
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>
```

