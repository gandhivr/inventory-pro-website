<?php
// Database connection settings
$host = 'localhost';          // Database server hostname
$dbname = 'inventory_management';  // Database name
$username = 'root';           // Database username (change if needed)
$password = '';               // Database password (change if needed)

try {
    // Create a new PDO instance for database connection with UTF-8 encoding
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password
    );

    // Set PDO to throw exceptions on database errors for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set the default fetch mode to associative array for easy access to columns by name
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // If connection fails, terminate the script and display error message
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started to manage user sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL of the application, useful for constructing absolute paths in links or redirects
$base_url = 'http://localhost/inventory-management/';
?>
