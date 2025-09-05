<?php
// Start session only if not already started to preserve session data
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include database connection and configuration settings
require_once '../config.php';

// Include common helper functions like authentication and sanitization
require_once '../includes/functions.php';

// Optional: Define base URL for creating absolute links in navigation,
// useful if different from current directory structure
if (!isset($base_url)) {
    $base_url = '/inventory-management/';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= $base_url ?>">
                <i class="fas fa-boxes me-2"></i>Inventory Manager
            </a>
            <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/manage_products.php">Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/manage_users.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/manage_orders.php">Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/reports.php">Reports</a></li>
                    <?php elseif ($_SESSION['role'] === 'supplier'): ?>
                        <li class="nav-item"><a class="nav-link" href="../supplier/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="../supplier/add_product.php">Add Product</a></li>
                        <li class="nav-item"><a class="nav-link" href="../supplier/manage_products.php">My Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="../supplier/view_orders.php">Orders</a></li>
                    <?php elseif ($_SESSION['role'] === 'buyer'): ?>
                        <li class="nav-item"><a class="nav-link" href="../buyer/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="../buyer/browse_products.php">Browse Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="../buyer/cart.php">Cart</a></li>
                        <li class="nav-item"><a class="nav-link" href="../buyer/order_history.php">Order History</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container mt-4">
