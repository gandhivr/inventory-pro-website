<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure the user is logged in and has the 'supplier' role.
// This function likely redirects unauthorized users.
checkRole('supplier');

$supplier_id = $_SESSION['user_id'];
$error = '';

// Fetch all products belonging to the logged-in supplier, ordered by creation date descending
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC");
    $stmt->execute([$supplier_id]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Capture any database error and initialize an empty product list
    $error = "Failed to load products: " . $e->getMessage();
    $products = [];
}

// Include site header (common to your site)
require_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Manage Products</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Button to add new product -->
    <a href="add_product.php" class="btn btn-success mb-3">
        <i class="fas fa-plus me-2"></i> Add New Product
    </a>

    <?php if (empty($products)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <!-- Table displaying all products for this supplier -->
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (USD)</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['description']) ?></td>
                            <td>$<?= number_format($product['price'], 2) ?></td>
                            <td><?= $product['quantity'] ?></td>
                            <td>
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" style="max-height: 50px;">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Edit product link -->
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <!-- Delete product form with confirmation -->
                                <form action="delete_product.php" method="GET" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Include site footer -->
<?php require_once '../includes/footer.php'; ?>
