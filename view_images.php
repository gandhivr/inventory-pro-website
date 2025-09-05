<?php
// Start the session to access user session data
session_start();

// Include config file for $pdo (database connection) and reusable functions
require_once 'config.php';
require_once 'includes/functions.php';

// Ensure the user is logged in by checking if 'role' is set in session
if (!isset($_SESSION['role'])) {
    // Redirect unauthenticated users to the home page or login page
    header('Location: index.php');
    exit;
}

// Prepare and execute SQL query to fetch all uploaded images with uploader name
// Images are ordered by upload time descending (newest first)
$stmt = $pdo->prepare("
    SELECT i.filepath, u.name as uploader 
    FROM images i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.uploaded_at DESC
");
$stmt->execute();

// Fetch all image records as an array
$images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Images Gallery</title>
    <!-- Bootstrap CSS for responsive grid and card styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h2>Uploaded Images</h2>
    <div class="row">
        <?php if (empty($images)): ?>
            <!-- If no images found, show message -->
            <p>No images uploaded yet.</p>
        <?php else: ?>
            <!-- Loop through each image and display inside Bootstrap cards -->
            <?php foreach ($images as $img): ?>
                <div class="col-sm-3 mb-3">
                    <div class="card">
                        <!-- Display image with safe URL encoding -->
                        <img src="<?= htmlspecialchars($img['filepath']) ?>" class="card-img-top" alt="Uploaded Image" />
                        <div class="card-body">
                            <!-- Display uploader's name safely -->
                            <p class="card-text"><small>Uploaded by: <?= htmlspecialchars($img['uploader']) ?></small></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
