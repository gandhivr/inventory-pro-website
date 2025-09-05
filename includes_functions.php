<?php
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to check user role
function checkRole($required_role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $required_role) {
        header('Location: ../login.php');
        exit();
    }
}

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Function to display alerts
function displayAlert($message, $type = 'info') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Function to format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Function to get user by ID
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Function to get products with pagination
function getProducts($pdo, $limit = 10, $offset = 0, $search = '') {
    $sql = "SELECT p.*, u.name as supplier_name FROM products p 
            JOIN users u ON p.supplier_id = u.id 
            WHERE p.product_name LIKE ?
            ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", $limit, $offset]);
    return $stmt->fetchAll();
}

// Function to count total products
function countProducts($pdo, $search = '') {
    $sql = "SELECT COUNT(*) FROM products WHERE product_name LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%"]);
    return $stmt->fetchColumn();
}
?>
