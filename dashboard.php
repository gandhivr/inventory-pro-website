<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is supplier
checkRole('supplier');

$supplier_id = $_SESSION['user_id'];

try {
    // Get supplier statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $total_products = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM products WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    $total_stock = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE p.supplier_id = ?
    ");
    $stmt->execute([$supplier_id]);
    $total_orders = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT SUM(o.total_price) FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE p.supplier_id = ? AND o.status != 'cancelled'
    ");
    $stmt->execute([$supplier_id]);
    $total_earnings = $stmt->fetchColumn() ?: 0;
    
    // Get recent orders for supplier products
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as buyer_name, p.product_name 
        FROM orders o 
        JOIN users u ON o.buyer_id = u.id 
        JOIN products p ON o.product_id = p.id 
        WHERE p.supplier_id = ? 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $recent_orders = $stmt->fetchAll();
    
    // Get low stock products
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE supplier_id = ? AND quantity <= 5 
        ORDER BY quantity ASC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $low_stock_products = $stmt->fetchAll();
    
    // Get top selling products
    $stmt = $pdo->prepare("
        SELECT p.product_name, p.price, SUM(o.quantity) as total_sold, SUM(o.total_price) as revenue
        FROM products p 
        LEFT JOIN orders o ON p.id = o.product_id AND o.status != 'cancelled'
        WHERE p.supplier_id = ? 
        GROUP BY p.id, p.product_name, p.price
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $stmt->execute([$supplier_id]);
    $top_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-store me-2"></i>Supplier Dashboard</h1>
        <div>
            <span class="badge bg-warning fs-6">Welcome, <?php echo $_SESSION['name']; ?></span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <div class="stat-number"><?php echo number_format($total_products); ?></div>
                            <div class="stat-label">My Products</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <div class="stat-number"><?php echo number_format($total_stock); ?></div>
                            <div class="stat-label">Total Stock</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-cubes"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <div class="stat-number"><?php echo formatCurrency($total_earnings); ?></div>
                            <div class="stat-label">Total Earnings</div>
                        </div>
                        <div class="col-auto">
                            <div class="stat-icon bg-white bg-opacity-25">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
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
                            <a href="add_product.php" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Add New Product
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="manage_products.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-edit me-2"></i>Manage Products
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="view_orders.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-list me-2"></i>View Orders
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-outline-secondary w-100" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                    <a href="view_orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No orders yet for your products.</p>
                            <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Buyer</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
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
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td><?php echo formatCurrency($order['total_price']); ?></td>
                                            <td>
                                                <?php
                                                $badge_class = match($order['status']) {
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'shipped' => 'primary',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
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

        <!-- Alerts & Notifications -->
        <div class="col-lg-4 mb-4">
            <!-- Low Stock Alert -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <p class="text-success text-center py-3">
                            <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                            All products are well stocked!
                        </p>
                    <?php else: ?>
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                    <small class="text-muted"><?php echo formatCurrency($product['price']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger"><?php echo $product['quantity']; ?> left</span>
                                    <br>
                                    <a href="manage_products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary mt-1">Restock</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-muted text-center py-4">No sales data available yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $max_sold = $top_products[0]['total_sold'] ?? 1;
                                    foreach ($top_products as $index => $product): 
                                        $percentage = $max_sold > 0 ? ($product['total_sold'] / $max_sold) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo formatCurrency($product['price']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $product['total_sold'] ?: 0; ?></span>
                                            </td>
                                            <td><?php echo formatCurrency($product['revenue'] ?: 0); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo round($percentage); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
