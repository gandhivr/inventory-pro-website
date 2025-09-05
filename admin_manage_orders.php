<?php
// Start session to access session variables
session_start();

// Include database configuration and helper functions
require_once '../config.php';
require_once '../includes/functions.php';

// Restrict access to admin users only
checkRole('admin');

$error = '';
$success = '';

// Handle order status updates submitted via POST
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']); // Sanitize order ID
    $new_status = sanitize($_POST['status']); // Sanitize status input

    // Define allowed statuses for validation
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        try {
            // Update order status safely with prepared statement
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $success = "Order status updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating order: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status selected.";
    }
}

// Fetch all orders joined with buyer, product, and supplier info
try {
    $stmt = $pdo->query("
        SELECT o.*, 
               u.name as buyer_name, u.email as buyer_email,
               p.product_name,
               s.name as supplier_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN products p ON o.product_id = p.id
        JOIN users s ON p.supplier_id = s.id
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-cube me-2"></i>InventoryPro
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_orders.php">Orders</a></li>
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
        <h1><i class="fas fa-shopping-cart me-2"></i>Manage Orders</h1>

        <!-- Show error message -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Show success message -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <p class="text-muted text-center py-4">No orders found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th><th>Buyer</th><th>Product</th><th>Supplier</th>
                                    <th>Quantity</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id']; ?></td>
                                        <td>
                                            <?= htmlspecialchars($order['buyer_name']); ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($order['buyer_email']); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($order['product_name']); ?></td>
                                        <td><?= htmlspecialchars($order['supplier_name']); ?></td>
                                        <td><?= $order['quantity']; ?></td>
                                        <td>$<?= number_format($order['total_price'], 2); ?></td>
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
                                            <span class="badge bg-<?= $badge_class; ?>"><?= ucfirst($order['status']); ?></span>
                                        </td>
                                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="updateOrderStatus(<?= $order['id']; ?>, '<?= $order['status']; ?>')">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="update_order_id">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select class="form-select" name="status" id="update_status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
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

    <!-- Bootstrap Bundle JS for modal and other components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show modal with current order ID and status pre-filled
        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('update_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html>
