<?php
require_once '../config.php';           // Include database & config setup
require_once '../includes/functions.php'; // Include helper functions

// Ensure user is authenticated as a supplier
checkRole('supplier');

$supplier_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and fetch form data
    $product_name = sanitize($_POST['product_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $image_path = '';  // To store path if image uploaded

    // Validate required fields
    if (!$product_name || $price <= 0 || $quantity < 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // If image uploaded, validate & save it
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageFile = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024;  // 5 MB max size

            // Validate file type
            if (!in_array(mime_content_type($imageFile['tmp_name']), $allowed_types)) {
                $error = "Invalid image format. Allowed: JPG, PNG, GIF.";
            }
            // Validate file size
            elseif ($imageFile['size'] > $max_size) {
                $error = "Image size must be under 5MB.";
            } else {
                // Create uploads directory if not exists
                $upload_dir = "../uploads/products/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                // Generate unique filename for image
                $ext = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $ext;
                $target_file = $upload_dir . $new_filename;

                // Move uploaded file to server directory
                if (move_uploaded_file($imageFile['tmp_name'], $target_file)) {
                    $image_path = "uploads/products/" . $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        // If no errors, store product data into database
        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO products (supplier_id, product_name, description, price, quantity, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$supplier_id, $product_name, $description, $price, $quantity, $image_path]);
            $success = "Product added successfully!";
        }
    }
}

// Include site header
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Add Product</h2>

    <!-- Display error message if validation or upload failed -->
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Display success message upon successful insert -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Product add form -->
    <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name *</label>
            <input type="text" id="product_name" name="product_name" class="form-control" required value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (USD) *</label>
            <input type="number" id="price" name="price" min="0.01" step="0.01" class="form-control" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity *</label>
            <input type="number" id="quantity" name="quantity" min="0" step="1" class="form-control" required value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Product Image</label>
            <input type="file" id="image" name="image" accept="image/*" class="form-control">
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary">Add Product</button>
        <!-- Back to dashboard button -->
        <a href="dashboard.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
