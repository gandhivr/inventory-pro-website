<?php
// Enable session handling for user verification and storing user data
session_start();

// Include database connection ($pdo) and helper functions like role checks
require_once '../config.php';
require_once '../includes/functions.php';

// Check if the user is logged in with the 'admin' role via helper function
if (!isLoggedIn()) {
    header('Location: ../index.php'); // Redirect to login if not logged in
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); // Redirect if not an admin
    exit();
}

// Initialize dashboard variables to zero or empty arrays by default
$total_suppliers = 0;
$total_buyers = 0;
$total_products = 0;
$total_orders = 0;
$total_revenue = 0;
$recent_orders = [];
$low_stock_products = [];
$error = '';

// Use try-catch block for safe querying with error handling
try {
    // Count active suppliers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supplier' AND status = 'active'");
    $total_suppliers = $stmt->fetchColumn() ?: 0;

    // Count active buyers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND status = 'active'");
    $total_buyers = $stmt->fetchColumn() ?: 0;

    // Count total products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn() ?: 0;

    // Count total orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn() ?: 0;

    // Calculate total revenue from all orders except cancelled ones
    $stmt = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'");
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // Fetch recent orders if any orders exist
    if ($total_orders > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as buyer_name, p.product_name 
            FROM orders o 
            JOIN users u ON o.buyer_id = u.id 
            JOIN products p ON o.product_id = p.id 
            ORDER BY o.order_date DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recent_orders = $stmt->fetchAll();
    }

    // Fetch low stock products (quantity <= 5) if products exist
    if ($total_products > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as supplier_name 
            FROM products p 
            JOIN users u ON p.supplier_id = u.id 
            WHERE p.quantity <= 5 
            ORDER BY p.quantity ASC 
            LIMIT 5
        ");
        $stmt->execute();
        $low_stock_products = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Capture database error message for display
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- Below is HTML that uses Bootstrap 5 for responsive layout and styling -->
<!-- Includes navigation bar with links and user dropdown menu -->
<!-- Displays error or success messages to the admin -->

<!-- Dashboard cards: total suppliers, buyers, products, orders -->
<!-- Large revenue card showing total revenue generated -->
<!-- Recent orders table showing latest five order details -->
<!-- Low stock alert panel listing products with stock <=5 -->
<!-- Quick action buttons for navigation and report printing -->

<!-- Uses FontAwesome icons for visual enhancement -->
<!-- Includes Bootstrap JS and CSS from CDN for UI interactions -->

<!-- This dashboard provides an at-a-glance summary and access to management pages -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - InventoryPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-cube me-2"></i>InventoryPro
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Orders</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
            <div>
                <span class="badge bg-success fs-6">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <div class="h2"><?php echo number_format($total_suppliers); ?></div>
                                <div>Total Suppliers</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-truck fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <div class="h2"><?php echo number_format($total_buyers); ?></div>
                                <div>Total Buyers</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <div class="h2"><?php echo number_format($total_products); ?></div>
                                <div>Total Products</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <div class="h2"><?php echo number_format($total_orders); ?></div>
                                <div>Total Orders</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body text-center">
                        <h2 class="display-4 mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                        <p class="mb-0">Total Revenue Generated</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p class="text-muted text-center py-4">No orders found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Buyer</th>
                                            <th>Product</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock_products)): ?>
                            <p class="text-success text-center py-4">All products are well stocked!</p>
                        <?php else: ?>
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                        <small class="text-muted">by <?php echo htmlspecialchars($product['supplier_name']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-danger"><?php echo $product['quantity']; ?> left</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="manage_products.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-box me-2"></i>Manage Products
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="manage_users.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="manage_orders.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>View Orders
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-outline-info w-100" onclick="window.print()">
                                    <i class="fas fa-chart-bar me-2"></i>Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
