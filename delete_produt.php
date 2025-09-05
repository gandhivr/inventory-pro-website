<?php
require_once '../config.php';          // Include database and configuration
require_once '../includes/functions.php';  // Include helper functions like checkRole and sanitize

// Ensure that the user is logged in and has the supplier role.
// This check will redirect unauthorized users to a different page.
checkRole('supplier');

$supplier_id = $_SESSION['user_id'];   // Get the logged-in supplier's user ID

// Get product ID from the query string (GET parameter) and validate it.
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    // Stop execution if the product ID is invalid or missing.
    die("Invalid product ID.");
}

try {
    // Attempt to fetch the product with the given ID that belongs to this supplier.
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $supplier_id]);
    $product = $stmt->fetch();

    // If the product is not found or does not belong to the supplier, stop execution.
    if (!$product) {
        die("Product not found or permission denied.");
    }

    // Begin a database transaction to ensure consistency.
    $pdo->beginTransaction();

    // Delete the product from the database.
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$product_id, $supplier_id]);

    // If there is an image file associated with the product, delete it from the server.
    if (!empty($product['image_path']) && file_exists("../" . $product['image_path'])) {
        // Use the error-suppression operator (@) to avoid warnings if delete fails
        @unlink("../" . $product['image_path']);
    }

    // Commit the transaction since deletion is successful.
    $pdo->commit();

    // Redirect the user back to the product management page with a success message.
    // Passing message via query string (optional).
    header("Location: manage_products.php?msg=Product+deleted+successfully");
    exit;

} catch (PDOException $e) {
    // Roll back transaction if anything went wrong, keeping data consistent.
    $pdo->rollBack();

    // Stop execution with error message.
    die("Error deleting product: " . $e->getMessage());
}
