<?php
// Start the session to store user data across pages
session_start();

// Include the database configuration file where $pdo (PDO instance) is defined
require_once 'config.php';

// Initialize an error message variable
$error = '';

// Check if the form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim whitespace from the login input (can be email or username)
    $login = trim($_POST['login']);
    // Get the password as-is from POST data
    $password = $_POST['password'];

    // Prepare an SQL statement to search for a user by email or username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) LIMIT 1");
    // Execute the query with the login value used for both placeholders
    $stmt->execute([$login, $login]);

    // Fetch the user record from the database
    $user = $stmt->fetch();

    // If a user exists and the hashed password matches the submitted password
    if ($user && password_verify($password, $user['password'])) {
        // Store user information in the session for later use
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // Optionally, echo the user's name (for debugging or confirmation)
        echo $_SESSION['name'];

        // Redirect to dashboard or another page (commented out here)
        // header("Location: dashboard.php");

        // Terminate script execution after successful login
        exit;
    } else {
        // Set error message on failed login attempts
        $error = "Login failed. Please try again.";
    }
}
?>

<!-- Simple HTML login form -->
<form method="POST">
    <input name="login" placeholder="Email or Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<!-- Display error message in red color if login fails -->
<?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
