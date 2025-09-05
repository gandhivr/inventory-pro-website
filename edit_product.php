<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure that the user is logged in and has the 'supplier' role.
// This function likely redirects unauthorized users.
checkRole('supplier');

$supplier_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get the product ID passed in the GET request, ensure it is a positive integer.
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    // If no valid product ID provided, stop execution and show error.
    die("Invalid product ID.");
}

// Load the existing product details for this supplier and product ID from database.
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $supplier_id]);
    $product = $stmt->fetch();

    if (!$product) {
        // Product does not exist or does not belong to this supplier.
        die("Product not found or no permission to edit.");
    }
} catch (PDOException $e) {
    // Database error when fetching product details
    die("Database error: " . $e->getMessage());
}

// Handle the form submission when request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs to avoid XSS and trim whitespace
    $product_name = sanitize($_POST['product_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    // Validate product name presence
    if (!$product_name) {
        $error = "Product name is required.";
    } 
    // Validate price is positive number
    elseif ($price <= 0) {
        $error = "Price must be > 0.";
    } 
    // Validate quantity is zero or positive integer
    elseif ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    }

    // If an image file is uploaded, validate the image
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['image'];
        // Allowed MIME types for image upload
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        // Max allowed size of 5MB
        $max_size = 5 * 1024 * 1024;

        // Check for upload errors
        if ($imageFile['error'] !== UPLOAD_ERR_OK) {
            $error = "Error uploading image.";
        } 
        // Validate MIME type of uploaded file matches allowed types
        elseif (!in_array(mime_content_type($imageFile['tmp_name']), $allowed_types)) {
            $error = "Invalid image type.";
        } 
        // Validate size limit
        elseif ($imageFile['size'] > $max_size) {
            $error = "Image size must be under 5MB.";
        }
    }

    // If no validation errors proceed with updating the database
    if (!$error) {
        try {
            // Start transaction to update product and image atomically
            $pdo->beginTransaction();

            // If new valid image uploaded
            if (isset($imageFile) && $imageFile['error'] === UPLOAD_ERR_OK) {
                $upload_dir = "../uploads/products/";
                // Create upload directory if it doesn't exist
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                // Generate a unique file name for the image
                $ext = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                // Move the uploaded image to the target directory
                if (!move_uploaded_file($imageFile['tmp_name'], $target_path)) {
                    throw new Exception("Failed to save uploaded image.");
                }

                // Delete old image file if present on server
                if (!empty($product['image_path']) && file_exists("../" . $product['image_path'])) {
                    @unlink("../" . $product['image_path']);
                }

                // Update product including new image path
                $stmt = $pdo->prepare("UPDATE products SET product_name = ?, description = ?, price = ?, quantity = ?, image_path = ? WHERE id = ? AND supplier_id = ?");
                $stmt->execute([$product_name, $description, $price, $quantity, "uploads/products/" . $new_filename, $product_id, $supplier_id]);
            } else {
                // Update product without changing image
                $stmt = $pdo->prepare("UPDATE products SET product_name = ?, description = ?, price = ?, quantity = ? WHERE id = ? AND supplier_id = ?");
                $stmt->execute([$product_name, $description, $price, $quantity, $product_id, $supplier_id]);
            }

            // Commit transaction
            $pdo->commit();

            $success = "Product updated successfully!";

            // Refresh product data to reflect update
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND supplier_id = ?");
            $stmt->execute([$product_id, $supplier_id]);
            $product = $stmt->fetch();

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// Include header template
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Edit Product</h2>

    <!-- Display error message if any -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Display success message if update successful -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Product edit form -->
    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name *</label>
            <input type="text" id="product_name" name="product_name" class="form-control" required value="<?= htmlspecialchars($product['product_name']) ?>">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price (USD) *</label>
            <input type="number" min="0.01" step="0.01" id="price" name="price" class="form-control" required value="<?= htmlspecialchars($product['price']) ?>">
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity *</label>
            <input type="number" min="0" step="1" id="quantity" name="quantity" class="form-control" required value="<?= htmlspecialchars($product['quantity']) ?>">
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Replace Image</label>
            <?php if (!empty($product['image_path']) && file_exists("../" . $product['image_path'])): ?>
                <div class="mb-2">
                    <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Current Image" style="max-height: 150px;">
                </div>
            <?php endif; ?>
            <input type="file" name="image" id="image" accept="image/*" class="form-control" />
            <small class="form-text text-muted">Leave empty to keep existing image.</small>
        </div>

        <!-- Submit and Back buttons -->
        <button type="submit" class="btn btn-primary">Update Product</button>
        <a href="dashboard.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
