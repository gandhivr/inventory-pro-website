<?php
// Start the session to work with user login state
session_start();

// Include the database connection and utility functions
require_once '../config.php';
require_once '../includes/functions.php';

// Restrict access: only logged-in users with role admin or supplier
if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    // Redirect unauthorized users away from this page
    header('Location: ../index.php');
    exit;
}

// Initialize variables for feedback messages
$error = '';
$success = '';

// Get logged-in user's ID (will be supplier ID for added product)
$supplier_id = $_SESSION['user_id'];

// Process form submission on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize incoming form data
    $product_name = trim($_POST['product_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $stock        = intval($_POST['quantity'] ?? 0);

    // By default, no image path
    $image_path = null;

    // Handle optional image upload
    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = '../uploads/'; // Directory where uploads will be stored
        
        // Create directory if it doesn't exist (with permissions)
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        // Generate a unique filename based on prefix and original extension
        $filename = uniqid('product_') . '.' . pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $target = $upload_dir . $filename;
        
        // Define allowed image file extensions for security
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check if file extension is allowed
        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, GIF allowed.";
        } 
        // Try to move uploaded file to target location
        elseif (move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
            // If successful, save relative image path for DB storage
            $image_path = 'uploads/' . $filename;
        } else {
            // Set error if we fail to upload
            $error = "Could not upload file.";
        }
    }

    // Proceed only if no errors and required fields are valid
    if (!$error && $product_name && $price > 0 && $stock >= 0) {
        // Insert new product record safely using prepared statements
        $stmt = $pdo->prepare("
            INSERT INTO products (supplier_id, product_name, description, price, quantity, image_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$supplier_id, $product_name, $description, $price, $stock, $image_path]);

        // Provide success feedback
        $success = "Product added successfully!";
    } 
    // If inputs were invalid but no upload error, set validation error message
    elseif (!$error) {
        $error = "Please fill all fields with valid values.";
    }
}
?>

<!-- Below is the HTML form for adding a new product -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <!-- Bootstrap 5 CSS for styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
</head>
<body>
    <div class="container mt-3">
        <!-- Back to dashboard link -->
        <a href="/dashboard.php" class="btn btn-secondary mb-3">
            <i class="fas fa-chevron-left"></i> Back to Dashboard
        </a>

        <div class="container mt-4">
            <h2>Add New Product</h2>

            <!-- Display error message if any -->
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Display success message if any -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Product addition form -->
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price *</label>
                        <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stock *</label>
                        <input type="number" name="quantity" class="form-control" min="0" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="product_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-success">Add Product</button>
                <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>

