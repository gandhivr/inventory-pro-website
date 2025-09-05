<?php
// upload_image.php

session_start();

require_once '../config.php';
require_once '../includes/functions.php';

// Only allow admin/supplier
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $imageFile = $_FILES['image'];
    $filename = basename($imageFile["name"]);
    $target_file = $target_dir . uniqid() . '_' . $filename;

    $imageFileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $check = getimagesize($imageFile["tmp_name"]);

    if ($check === false) {
        $error = "Please upload a valid image file.";
    } elseif ($imageFile["size"] > 5 * 1024 * 1024) {
        $error = "Image size must be less than 5MB.";
    } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    } else {
        if (move_uploaded_file($imageFile["tmp_name"], $target_file)) {
            // Save in database linked to user who uploaded & date
            $stmt = $pdo->prepare("INSERT INTO images (user_id, filepath, uploaded_at) VALUES (?, ?, NOW())");
            if ($stmt->execute([$_SESSION['user_id'], $target_file])) {
                $success = "Image uploaded successfully!";
            } else {
                $error = "Failed to record image info in database.";
            }
        } else {
            $error = "Failed to upload the image.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Upload Image</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Upload Image</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <input type="file" name="image" accept="image/*" class="form-control" required />
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
        <a href="../index.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
