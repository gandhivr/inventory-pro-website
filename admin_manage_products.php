<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Access control: only admin or supplier allowed
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];  // Get current user ID from session
$role = $_SESSION['role'];        // Get user role from session

// Fetch active (not soft-deleted) products accordingly based on role
if ($role === 'admin') {
    // Admin can see all products along with their supplier name
    $stmt = $pdo->query("SELECT p.*, u.name AS supplier_name FROM products p LEFT JOIN users u ON p.supplier_id = u.id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
} else {
    // Supplier can see only their own products that are not soft deleted
    $stmt = $pdo->prepare("SELECT * FROM products WHERE supplier_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll();
}

// Handle Soft Delete and Hard Delete POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['soft_delete'])) {
        // Soft delete sets deleted_at timestamp to hide the product
        $prod_id = intval($_POST['product_id']);
        $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$prod_id]);
        header('Location: manage_products.php?msg=soft_deleted');
        exit;
    }
    if (isset($_POST['hard_delete']) && $role === 'admin') {
        // Hard delete removes the product permanently (admins only)
        $prod_id = intval($_POST['product_id']);
        // Delete image file from server if exists
        $stmtImg = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
        $stmtImg->execute([$prod_id]);
        $img = $stmtImg->fetchColumn();
        if ($img && file_exists('../' . $img)) {
            unlink('../' . $img);
        }
        // Delete product record from database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$prod_id]);
        header('Location: manage_products.php?msg=hard_deleted');
        exit;
    }
}

// Show success messages based on action performed
$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'soft_deleted') {
        $message = "Product soft deleted successfully.";
    } elseif ($_GET['msg'] === 'hard_deleted') {
        $message = "Product permanently deleted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic meta tags and Bootstrap CSS -->
    <meta charset="UTF-8" />
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h2>Manage Products</h2>
    
    <!-- Display any action messages -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Back to Dashboard link, different for admin and supplier -->
    <?php if ($role === 'admin'): ?>
        <a href="../admin/dashboard.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    <?php else: ?>
        <a href="dashboard.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    <?php endif; ?>

    <!-- Add product and View deleted products buttons -->
    <a href="add_product.php" class="btn btn-success mb-3 ms-2">+ Add New Product</a>
    <a href="manage_deleted_products.php" class="btn btn-info mb-3 ms-2">View Deleted Products</a>
    
    <!-- Products table -->
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th>
                <?php if ($role === 'admin'): ?><th>Supplier</th><?php endif; ?>
                <th>Image</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="<?= $role === 'admin' ? '8' : '7' ?>" class="text-center">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                <tr>
                    <td><?= htmlspecialchars($prod['id']) ?></td>
                    <td><?= htmlspecialchars($prod['product_name']) ?></td>
                    <td><?= htmlspecialchars($prod['description']) ?></td>
                    <td>$<?= number_format($prod['price'], 2) ?></td>
                    <td><?= htmlspecialchars($prod['quantity']) ?></td>
                    <?php if ($role === 'admin'): ?>
                        <td><?= htmlspecialchars($prod['supplier_name'] ?? 'N/A') ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if (!empty($prod['image_path'])): ?>
                            <img src="../<?= htmlspecialchars($prod['image_path']) ?>" alt="Image" style="max-height:50px;" />
                        <?php else: ?>N/A<?php endif; ?>
                    </td>
                    <td>
                        <!-- Edit button linking to edit page -->
                        <a href="edit_product.php?id=<?= urlencode($prod['id']) ?>" class="btn btn-primary btn-sm mb-1">Edit</a>

                        <!-- Soft Delete form button -->
                        <form method="post" style="display:inline;" onsubmit="return confirm('Soft delete will hide this product. Continue?');">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <button type="submit" name="soft_delete" class="btn btn-warning btn-sm mb-1">Soft Delete</button>
                        </form>

                        <!-- Hard Delete only for admin -->
                        <?php if ($role === 'admin'): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('This will permanently delete this product! Are you sure?');">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <button type="submit" name="hard_delete" class="btn btn-danger btn-sm mb-1">Hard Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
