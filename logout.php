<?php
// Start the session to access the current session data
session_start();

// Unset all session variables by assigning an empty array
$_SESSION = array();

// If the session uses cookies, delete the session cookie to fully remove session data from the client
if (ini_get("session.use_cookies")) {
    // Retrieve current cookie parameters
    $params = session_get_cookie_params();

    // Set the session cookie with an expiration time in the past to invalidate it
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session data on the server side
session_destroy();

// Redirect user to the homepage or login page after logout
header('Location: index.php');
exit();
?>
