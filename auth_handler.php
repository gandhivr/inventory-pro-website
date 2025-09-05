<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Set JSON response header for API-like response
header('Content-Type: application/json');

// Get the action from custom HTTP header 'X-Action'
$action = $_SERVER['HTTP_X_ACTION'] ?? '';

// Route the request to proper handler based on action
if ($action === 'login') {
    handleLogin();
} elseif ($action === 'register') {
    handleRegister();
} else {
    // Invalid or missing action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Handle login requests.
 * Validates input, verifies credentials, sets session variables, returns JSON response.
 */
function handleLogin() {
    global $pdo;

    // Sanitize and get input fields
    $login_field = sanitize($_POST['login_field'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate presence of username/email and password
    if (empty($login_field) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        return;
    }

    try {
        // Lookup user by email or username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_field, $login_field]);
        $user = $stmt->fetch();

        // Verify user exists and password matches hashed password in DB
        if ($user && password_verify($password, $user['password'])) {
            // Check if user account is blocked
            if ($user['status'] === 'blocked') {
                echo json_encode(['success' => false, 'message' => 'Your account has been blocked.']);
                return;
            }

            // Set session variables for logged-in user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Determine redirect URL based on user role using match expression
            $redirect = match($user['role']) {
                'admin' => 'admin/dashboard.php',
                'supplier' => 'supplier/dashboard.php',
                'buyer' => 'buyer/dashboard.php',
                default => 'index.php'
            };

            // Return success JSON with redirect path
            echo json_encode(['success' => true, 'redirect' => $redirect]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email/username or password.']);
        }

    } catch (PDOException $e) {
        // Return database error message
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle user registration.
 * Validates input, checks for uniqueness, inserts user and profile records, transactionally.
 */
function handleRegister() {
    global $pdo;

    // Sanitize and collect inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $company_name = sanitize($_POST['company_name'] ?? '');

    // Basic validation of required fields
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        return;
    }

    // Validate valid role selection
    if (!in_array($role, ['admin', 'supplier', 'buyer'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
        return;
    }

    // Require username if role is buyer
    if ($role === 'buyer' && empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required for buyers.']);
        return;
    }

    // Enforce minimum password length
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        return;
    }

    try {
        // Start transaction for atomic inserts
        $pdo->beginTransaction();

        // Check if email is already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            $pdo->rollBack();
            return;
        }

        // If buyer, check if username is already taken
        if ($role === 'buyer' && !empty($username)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already taken.']);
                $pdo->rollBack();
                return;
            }
        }

        // Hash user password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with provided data
        $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $username, $hashed_password, $role]);

        // Get inserted user ID for creating profile
        $user_id = $pdo->lastInsertId();

        // Insert role-specific profile data if needed
        if ($role === 'supplier') {
            $stmt = $pdo->prepare("INSERT INTO supplier_profile (user_id, company_name, address, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $company_name, $address, $phone]);
        } elseif ($role === 'buyer') {
            $stmt = $pdo->prepare("INSERT INTO buyer_profile (user_id, address, phone) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $address, $phone]);
        }

        // Commit transaction if all inserts succeeded
        $pdo->commit();

        // Return success message
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);

    } catch (PDOException $e) {
        // Roll back if something failed and return error message
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}
?>
