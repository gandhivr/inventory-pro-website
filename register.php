<?php
// Start session for maintaining state, if needed later
session_start();

// Include database config: defines $pdo as a PDO instance with UTF-8 encoding
require_once 'config.php';

// Helper function to sanitize input by trimming and escaping HTML special characters
function sanitize($v) { 
    return htmlspecialchars(trim($v)); 
}

// Initialize an error message variable to track validation errors
$error = '';

// Check if form was submitted using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user inputs to prevent XSS and unwanted whitespace
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Password should not be sanitized as it might alter content
    $role = $_POST['role']; // Expected values: 'buyer', 'supplier', or 'admin'

    // Basic validation checks: required fields and role-specific username requirement
    if (!$name || !$email || !$password || !$role) {
        $error = "All main fields required.";
    } elseif ($role === 'buyer' && !$username) {
        // For buyers, username is mandatory
        $error = "Username is required for buyers.";
    } elseif (strlen($password) < 6) {
        // Enforce minimum password length
        $error = "Password must be at least 6 chars.";
    } else {
        // Check if email already exists in the database to enforce uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $error = "Email taken.";

        // If role is buyer, also check if username is unique
        if ($role === 'buyer') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) $error = "Username taken.";
        }

        // If no errors, proceed to insert the new user
        if (!$error) {
            // Hash the password securely before storing
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'buyer') {
                // Insert buyer records with username
                $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $username, $hash, $role]);
            } else {
                // Insert supplier/admin records without username
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $role]);
            }

            // Inform user of successful registration with a login link and exit script
            echo "<p>Registration complete. <a href='login.php'>Login here</a></p>";
            exit;
        }
    }
}
?>

<!-- Registration HTML form with input fields for user details -->
<form method="POST">
    <input name="name" placeholder="Full name"><br>
    <input name="email" type="email" placeholder="Email"><br>
    <select name="role">
        <option value="buyer">Buyer</option>
        <option value="supplier">Supplier</option>
        <option value="admin">Admin</option>
    </select><br>
    <input name="username" placeholder="Username (buyers only)"><br>
    <input name="password" type="password" placeholder="Password"><br>
    <button type="submit">Register</button>
</form>

<!-- Show error messages in red if validation fails -->
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
