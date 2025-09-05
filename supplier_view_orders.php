<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Check user authentication and ensure user role is 'supplier'
// If not logged in or not a supplier, redirect to home page
if (!isLoggedIn() || $_SESSION['role'] !== 'supplier') {
    header('Location: ../index.php');
    exit();
}

$supplier_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle order status update requests submitted via POST
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Get submitted order ID and new status
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize($_POST['status']);

    // Define allowed status values for validation
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    // Validate new status
    if (in_array($new_status, $valid_statuses)) {
        try {
            // Update order status only if the order belongs to a product owned by this supplier
            $stmt = $pdo->prepare("
                UPDATE orders o
                JOIN products p ON o.product_id = p.id
                SET o.status = ?
                WHERE o.id = ? AND p.supplier_id = ?
            ");
            $stmt->execute([$new_status, $order_id, $supplier_id]);
            $success = "Order status updated successfully!";
        } catch (PDOException $e) {
            // Capture any database error during update
            $error = "Error updating order: " . $e->getMessage();
        }
    } else {
        // Handle invalid status values
        $error = "Invalid status selected.";
    }
}

// Fetch all orders relating to this supplier's products along with buyer info
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               p.product_name,
               u.name as buyer_name, u.email as buyer_email,
               bp.address as buyer_address, bp.phone as buyer_phone
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.buyer_id = u.id
        LEFT JOIN buyer_profile bp ON u.id = bp.user_id
        WHERE p.supplier_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$supplier_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle DB errors during orders retrieval
    $error = "Error loading orders: " . $e->getMessage();
    $orders = [];
}

// Compute dashboard stats from retrieved orders:
// Count total orders
$total_orders = count($orders);
// Count number of pending orders
$pending_orders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
// Count number of completed (delivered) orders
$completed_orders = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
// Calculate total revenue from non-cancelled orders
$total_revenue = array_sum(array_column(array_filter($orders, fn($o) => $o['status'] !== 'cancelled'), 'total_price'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Orders - Supplier</title>
    <!-- Bootstrap CSS and FontAwesome for UI styling and icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../css/style.css" rel="stylesheet" />
</head>
<body>
    <!-- Navigation bar with navigation links and user dropdown -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-cube me-2"></i>InventoryPro
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_product.php">Add Product</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_products.php">My Products</a></li>
                    <li class="nav-item"><a class="nav-link active" href="view_orders.php">Orders</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-shopping-cart me-2"></i>Product Orders</h1>

        <!-- Display any error messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Display any success messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard summary cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white text-center">
                    <div class="card-body">
                        <h3><?= $total_orders ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white text-center">
                    <div class="card-body">
                        <h3><?= $pending_orders ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white text-center">
                    <div class="card-body">
                        <h3><?= $completed_orders ?></h3>
                        <p>Completed Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white text-center">
                    <div class="card-body">
                        <h3>$<?= number_format($total_revenue, 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders listing table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>All Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>No orders found for your products yet.</p>
                        <a href="add_product.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add More Products</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Buyer</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($order['buyer_name']) ?></strong><br />
                                            <small class="text-muted"><?= htmlspecialchars($order['buyer_email']) ?></small>
                                            <?php if ($order['buyer_phone']): ?>
                                                <br /><small class="text-muted"><?= htmlspecialchars($order['buyer_phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($order['product_name']) ?></td>
                                        <td><?= $order['quantity'] ?></td>
                                        <td>$<?= number_format($order['total_price'], 2) ?></td>
                                        <td>
                                            <?php 
                                                $badge_class = match($order['status']) {
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'shipped' => 'primary',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary',
                                                };
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($order['status']) ?></span>
                                        </td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <?php if (!in_array($order['status'], ['delivered', 'cancelled'])): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="updateOrderStatus(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($order['buyer_address']): ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="showAddress('<?= htmlspecialchars($order['buyer_address']) ?>')">
                                                    <i class="fas fa-map-marker-alt"></i> Address
                                                </button>
                                            <?php endif; ?>
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

    <!-- Modal: Update Order Status -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status" />
                        <input type="hidden" name="order_id" id="update_order_id" />
                        <div class="mb-3">
                            <label for="update_status" class="form-label">Order Status</label>
                            <select class="form-select" name="status" id="update_status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <strong>Status Guide:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Confirmed:</strong> Order accepted and processing</li>
                                <li><strong>Shipped:</strong> Order dispatched to buyer</li>
                                <li><strong>Delivered:</strong> Order completed successfully</li>
                                <li><strong>Cancelled:</strong> Order cancelled/refunded</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: View Buyer Address -->
    <div class="modal fade" id="addressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buyer Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="address_content"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show modal to update order status and preset the fields
        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('update_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        // Show modal displaying buyer address
        function showAddress(address) {
            document.getElementById('address_content').textContent = address;
            new bootstrap.Modal(document.getElementById('addressModal')).show();
        }
    </script>
</body>
</html>
