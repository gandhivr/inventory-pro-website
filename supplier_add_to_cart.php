<?php
// Include database config and helper functions
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in and has role 'buyer'
// If not, respond with JSON unauthorized error and exit
if (!isLoggedIn() || $_SESSION['role'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if the request method is POST (API expects POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Decode JSON input from the raw request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Sanitize and validate product ID and quantity inputs
    $product_id = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);

    // Validate input values; if invalid, return JSON error and exit
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit();
    }

    try {
        // Query the product's stock quantity from DB by product ID
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        // If product not found, return JSON error and exit
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }

        // Check if requested quantity is available in stock
        if ($product['quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }

        // Initialize cart session array if it doesn't exist yet
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // If product already in cart, increment quantity; else add new entry
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }

        // Ensure quantity in cart does not exceed available stock
        if ($_SESSION['cart'][$product_id] > $product['quantity']) {
            $_SESSION['cart'][$product_id] = $product['quantity'];
        }

        // Return JSON success message
        echo json_encode(['success' => true, 'message' => 'Product added to cart']);

    } catch (PDOException $e) {
        // Handle any DB errors by returning JSON error message
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    // If request is not POST, return JSON error
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
