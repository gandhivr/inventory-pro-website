<?php
// ALWAYS start the session before any output (including whitespace)
session_start();

// Include the configuration file to setup database connection ($pdo)
require_once '../config.php';

// Include common helper functions (e.g., authentication, sanitization)
require_once '../includes/functions.php';

// Ensure the current user is logged in as a buyer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    // Redirect unauthorized users to the homepage
    header('Location: ../index.php');
    exit;
}

// Fetch products that have available stock (> 0)
// Also include supplier name using LEFT JOIN with users table
$stmt = $pdo->query("
    SELECT p.*, u.name AS supplier_name 
    FROM products p 
    LEFT JOIN users u ON p.supplier_id = u.id 
    WHERE p.quantity > 0 
    ORDER BY p.created_at DESC
");
// Fetch all product records as associative array
$products = $stmt->fetchAll();

// Fetch the recent 10 orders made by the logged-in buyer
$buyer_id = $_SESSION['user_id'];

// Prepare statement to get orders joined with product and supplier info
$stmt = $pdo->prepare("
    SELECT o.*, p.product_name, u.name AS supplier_name
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users u ON p.supplier_id = u.id 
    WHERE o.buyer_id = ?
    ORDER BY o.order_date DESC 
    LIMIT 10
");

// Execute the prepared statement with buyer's user ID
$stmt->execute([$buyer_id]);

// Fetch the orders as an associative array
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Buyer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; padding-top: 20px; }
        .card { border: none; box-shadow: 0 2px 15px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
        .card-header { font-size: 1.1rem; padding: 14px 20px; }
        .card-body { padding: 20px; }
        .table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .table th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 12px 15px; }
        .table td { padding: 12px 15px; vertical-align: middle; }
        .table tr:not(:last-child) td { border-bottom: 1px solid #e9ecef; }
        .table-responsive { border-radius: 8px; overflow: hidden; }
        .badge { font-size: 0.85rem; padding: 6px 10px; border-radius: 14px; }
        .alert { margin-top: 16px; border-radius: 8px; }
        .btn-primary { background-color: #0d6efd; border: none; padding: 6px 14px; }
        .btn-primary:hover { background-color: #0b5ed7; }
        .btn-secondary { background-color: #6c757d; border: none; padding: 6px 14px; }
        .btn-secondary:hover { background-color: #5c636a; }
        .btn-sm { padding: 5px 10px; font-size: 0.875rem; }
        .btn-danger { background-color: #dc3545; border: none; }
        .btn-danger:hover { background-color: #bb2d3b; }
        input[type="number"] { width: 70px; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; }
        img { border-radius: 6px; max-height: 60px; object-fit: contain; }
        .action-buttons { display: flex; gap: 6px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logout Button -->
        <div class="d-flex justify-content-end mb-4">
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <h1 class="mb-4"><i class="fas fa-home"></i> Buyer Dashboard</h1>
        <?php if (isset($_GET['order']) && $_GET['order'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Order placed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-box"></i> Available Products for <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="text-muted text-center">No products available right now.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Supplier</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Image</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><b><?= htmlspecialchars($product['product_name']) ?></b></td>
                                                <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                                <td>$<?= number_format($product['price'], 2) ?></td>
                                                <td><?= $product['quantity'] ?></td>
                                                <td>
                                                    <?php if (!empty($product['image_path'])): ?>
                                                        <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image">
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <form method="POST" action="place_order.php" class="mb-0">
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" class="form-control" required>
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-shopping-cart"></i> Buy
                                                        </button>
                                                    </form>
                                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-secondary btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
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
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-clipboard-list"></i> Recent Orders
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-muted text-center">You have not placed any orders yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Product</th>
                                            <th>Supplier</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= $order['id'] ?></td>
                                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                                <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                                                <td><?= $order['quantity'] ?></td>
                                                <td>$<?= number_format($order['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?=
                                                        match($order['status']) {
                                                            'pending' => 'warning',
                                                            'confirmed' => 'info',
                                                            'shipped' => 'primary',
                                                            'delivered' => 'success',
                                                            'cancelled' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?= ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
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
    <script src="https://kit.fontawesome.com/a2e2e0cde8.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
