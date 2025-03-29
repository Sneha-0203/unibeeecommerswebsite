<?php
// includes/admin_sidebar.php - Sidebar for admin pages
// Make sure session is started in the main file before including this
?>
<div class="list-group shadow mb-4">
    <a href="admin_dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>
    <a href="admin_profile.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_profile.php' ? 'active' : ''; ?>">
        <i class="bi bi-person-circle me-2"></i> My Profile
    </a>
</div>

<div class="list-group shadow mb-4">
    <div class="list-group-item bg-light fw-bold">Inventory Management</div>
    <a href="admin_products.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_products.php' ? 'active' : ''; ?>">
        <i class="bi bi-box me-2"></i> Products
    </a>
    <a href="admin_categories.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_categories.php' ? 'active' : ''; ?>">
        <i class="bi bi-tags me-2"></i> Categories
    </a>
    <a href="admin_inventory.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_inventory.php' ? 'active' : ''; ?>">
        <i class="bi bi-archive me-2"></i> Stock Management
    </a>
</div>

<div class="list-group shadow mb-4">
    <div class="list-group-item bg-light fw-bold">Sales & Customers</div>
    <a href="admin_orders.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>">
        <i class="bi bi-cart3 me-2"></i> Orders
    </a>
    <a href="admin_users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
        <i class="bi bi-people me-2"></i> Customers
    </a>
</div>

<div class="list-group shadow">
    <div class="list-group-item bg-light fw-bold">Configuration</div>
    <a href="admin_settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>">
        <i class="bi bi-gear me-2"></i> Store Settings
    </a>
    <a href="admin_admins.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'admin_admins.php' ? 'active' : ''; ?>">
        <i class="bi bi-shield-lock me-2"></i> Admin Accounts
    </a>
    <a href="admin_logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
    </a>
</div>