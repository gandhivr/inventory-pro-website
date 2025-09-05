<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header('Location: ../index.php'); exit;
}

// Fetch all products
$stmt = $pdo->query("SELECT p.*, u.name AS supplier_name FROM products p LEFT JOIN users u ON p.supplier_id = u.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8"><title>Available Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
</head><body>
<div class="container mt-4">
    <h2>Available Products</h2>
    <table class="table table-bordered">
        <thead><tr>
            <th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><th>Supplier</th><th>Image</th>
        </tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= $product['id'] ?></td>
            <td><?= htmlspecialchars($product['product_name']) ?></td>
            <td><?= htmlspecialchars($product['description']) ?></td>
            <td><?= number_format($product['price'],2) ?></td>
            <td><?= $product['quantity'] ?></td>
            <td><?= htmlspecialchars($product['supplier_name']) ?></td>
            <td>
                <?php if (!empty($product['image_path'])): ?>
                    <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" style="max-width:80px;">
                <?php else: ?>N/A<?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body></html>
