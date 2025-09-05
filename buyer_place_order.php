<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: ../index.php');
    exit;
}

// Handle POST when an order is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $buyer_id = $_SESSION['user_id'];

    // OPTIONAL: Add validation for quantity and product availability here

    // Fetch product price
    $stmt = $pdo->prepare("SELECT price, quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product && $product['quantity'] >= $quantity) {
        $total_price = $product['price'] * $quantity;

        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, product_id, quantity, total_price, status, order_date) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$buyer_id, $product_id, $quantity, $total_price]);

        // Reduce product stock
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $product_id]);

        header("Location: dashboard.php?order=success");
        exit;
    } else {
        header("Location: dashboard.php?order=fail");
        exit;
    }
}
header("Location: dashboard.php");
exit;
?>
