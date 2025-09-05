<?php
// Start session for current user session management
session_start();

// Include your PDO database connection and helper functions
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure user is logged in and has either admin or supplier role
// If not, redirect unauthorized users to homepage
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    header('Location: ../index.php');
    exit;
}

// Sanitize and get product ID from GET parameters, verify it's valid (>0)
$product_id = intval($_GET['id'] ?? 0);

// Get current logged-in user ID and role from session for access checks
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// If product ID is invalid, stop execution with message
if ($product_id <= 0) {
    die("Invalid product ID.");
}

// Fetch product details according to role:
// Admin can see any product by ID
// Supplier can only access products they supply
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $user_id]);
}

// Fetch the product row
$product = $stmt->fetch();

// If product not found or user lacks permission (supplier -> other’s product), stop execution
if (!$product) {
    die('Product not found or no permission.');
}

// Initialize variables to hold feedback messages to display
$error = '';
$success = '';

// Handle form submission for product updates (when update button pressed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Retrieve and sanitize input fields from form submission
    $product_name = trim($_POST['product_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $stock        = intval($_POST['quantity'] ?? 0);

    // Prepare to handle image upload; default is existing image path
    $image_path = $product['image_path'];

    // Check if a new image file was uploaded
    if (!empty($_FILES['product_image']['name'])) {
        // Define folder to store uploaded images; create if missing
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // Create a unique filename for uploaded file to avoid overwriting
        $filename = uniqid('product_') . '.' . pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $target = $upload_dir . $filename;

        // Define allowed image extensions for security
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Validate file extension and move uploaded file to target location
        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, PNG, GIF allowed.';
        } elseif (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
            // Update image_path to new uploaded image
            $image_path = 'uploads/' . $filename;
        } else {
            $error = 'Could not upload file.';
        }
    }

    // If no validation errors and all required inputs are valid, update product in database
    if (!$error && $product_name && $price > 0 && $stock >= 0) {
        $stmt = $pdo->prepare(
            "UPDATE products SET product_name=?, description=?, price=?, quantity=?, image_path=? WHERE id=?"
        );
        $stmt->execute([$product_name, $description, $price, $stock, $image_path, $product_id]);
        $success = "Product updated successfully!";
    } elseif (!$error) {
        // If data invalid but no upload error, set error message
        $error = "Please fill all fields with valid values.";
    }
}

// Handle soft delete: mark product as deleted by setting deleted_at timestamp
// This hides product from active listings without deleting data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete'])) {
    $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$product_id]);
    header("Location: manage_products.php?msg=soft_deleted");
    exit;
}

// Handle hard delete: permanently deletes product and its image (allowed only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hard_delete']) && $role === 'admin') {
    // Optionally delete image file from file system
    if ($product['image_path'] && file_exists('../' . $product['image_path'])) {
        unlink('../' . $product['image_path']);
    }
    // Remove product record from database completely
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header("Location: manage_products.php?msg=hard_deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body>
<div class="container mt-4">
    <h2>Edit Product</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Product Name *</label>
            <input type="text" name="product_name" class="form-control" required value="<?= htmlspecialchars($product['product_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Price (USD) *</label>
            <input type="number" min="0.01" step="0.01" name="price" class="form-control" required value="<?= htmlspecialchars($product['price']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Stock *</label>
            <input type="number" min="0" step="1" name="quantity" class="form-control" required value="<?= htmlspecialchars($product['quantity']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Change Image</label>
            <?php if ($product['image_path']): ?>
                <div><img src="../<?= htmlspecialchars($product['image_path']) ?>" style="height:50px;"></div>
            <?php endif; ?>
            <input type="file" name="product_image" class="form-control"/>
        </div>

        <button type="submit" name="update" class="btn btn-primary">Update</button>
        <a href="manage_products.php" class="btn btn-secondary ms-2">Back</a>
        <button type="submit" name="soft_delete" class="btn btn-warning ms-2" onclick="return confirm('Soft delete will hide the product but not remove it. Continue?');">Soft Delete</button>
        <?php if ($role === 'admin'): ?>
            <button type="submit" name="hard_delete" class="btn btn-danger ms-2" onclick="return confirm('HARD DELETE will permanently remove this product. This cannot be undone!');">Hard Delete</button>
        <?php endif; ?>
    </form>
    <?php if ($product['deleted_at']): ?>
        <div class="alert alert-warning mt-3">This item is soft-deleted (hidden from buyers/suppliers).</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
