<?php
// Start session for current user session management
session_start();

require_once '../config.php'; // Include your PDO database connection config
require_once '../includes/functions.php'; // Include common functions (e.g., role checks)

// Ensure user is logged in and is either admin or supplier
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    header('Location: ../index.php'); // Redirect unauthorized users to index page
    exit;
}

$user_id = $_SESSION['user_id']; // Logged in user ID
$role = $_SESSION['role']; // Logged in user role

// Retrieve all soft-deleted products based on role
if ($role === 'admin') {
    // Admin gets all soft deleted products with supplier info
    $stmt = $pdo->query("SELECT p.*, u.name AS supplier_name 
                         FROM products p 
                         LEFT JOIN users u ON p.supplier_id = u.id 
                         WHERE p.deleted_at IS NOT NULL 
                         ORDER BY p.deleted_at DESC");
    $deleted_products = $stmt->fetchAll();
} else {
    // Supplier gets only their own soft deleted products
    $stmt = $pdo->prepare("SELECT * FROM products 
                           WHERE supplier_id = ? AND deleted_at IS NOT NULL 
                           ORDER BY deleted_at DESC");
    $stmt->execute([$user_id]);
    $deleted_products = $stmt->fetchAll();
}

// Handle restoration of soft-deleted product upon form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    $prod_id = intval($_POST['product_id']); // Sanitize product ID
    $stmt = $pdo->prepare("UPDATE products SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$prod_id]); // Restore by setting deleted_at to NULL
    header('Location: manage_deleted_products.php?msg=restored'); // Redirect after restore
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Deleted Products</title>
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Light gray background for page */
        body {
            background-color: #f8f9fa;
        }
        /* Container styling with padding and shadow */
        .table-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        /* Minimum width of restore button for consistency */
        .btn-restore {
            min-width: 90px;
        }
    </style>
</head>
<body>
<div class="container table-container">
    <h2 class="mb-4">Deleted Products</h2>

    <!-- Back button to main product management page -->
    <a href="manage_products.php" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left me-2"></i> Back to Products
    </a>

    <!-- Show success alert if redirected after restore -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'restored'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Product restored successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Responsive table for deleted products -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <?php if ($role === 'admin'): ?>
                        <th>Supplier</th>
                    <?php endif; ?>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deleted_products)): ?>
                    <tr>
                        <td colspan="<?= $role === 'admin' ? '7' : '6' ?>" class="text-center text-muted">No deleted products found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deleted_products as $prod): ?>
                        <tr>
                            <td><?= $prod['id'] ?></td>
                            <td><?= htmlspecialchars($prod['product_name']) ?></td>
                            <td><?= htmlspecialchars($prod['description']) ?></td>
                            <td>$<?= number_format($prod['price'], 2) ?></td>
                            <td><?= $prod['quantity'] ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td><?= htmlspecialchars($prod['supplier_name'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td><?= $prod['deleted_at'] ?></td>
                            <td>
                                <!-- Restore form with confirmation dialog -->
                                <form method="post" class="d-inline" onsubmit="return confirm('Restore this product?');">
                                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                    <button type="submit" name="restore" class="btn btn-success btn-sm btn-restore">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS Bundle & FontAwesome -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a2d9d6d1d6.js" crossorigin="anonymous"></script>
</body>
</html>
