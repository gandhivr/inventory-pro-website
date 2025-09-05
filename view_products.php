<?php
// Start the session to have access to session variables
session_start();

// Include database connection settings and common functions
require_once '../config.php';
require_once '../includes/functions.php';

// Ensure the user is logged in as a buyer; redirect to home if not
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header('Location: ../index.php');
    exit;
}

// Prepare and execute a query to get all products,
// along with their supplier's name, ordered by newest first.
// Uses LEFT JOIN to also include any products even if the supplier info is missing.
// The result is fetched as an associative array.
$stmt = $pdo->query("
    SELECT p.*, u.name AS supplier_name 
    FROM products p 
    LEFT JOIN users u ON p.supplier_id = u.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Available Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h2>Products for Purchase</h2>
    <table class="table table-bordered align-middle">
        <thead>
            <tr><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><th>Supplier</th><th>Image</th></tr>
        </thead>
        <tbody>
            <?php foreach ($products as $prod): ?>
                <tr>
                    <td><?= htmlspecialchars($prod['product_name']) ?></td>
                    <td><?= htmlspecialchars($prod['description']) ?></td>
                    <td>$<?= number_format($prod['price'], 2) ?></td>
                    <td><?= $prod['quantity'] ?></td>
                    <td><?= htmlspecialchars($prod['supplier_name']) ?></td>
                    <td>
                        <?php if (!empty($prod['image_path'])): ?>
                            <img src="../<?= htmlspecialchars($prod['image_path']) ?>" alt="Product Image" style="max-width:80px;" />
                        <?php else: ?>N/A<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
