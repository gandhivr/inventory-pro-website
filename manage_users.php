<?php
// Start session to use session data
session_start();

// Include database config and helper functions
require_once '../config.php';
require_once '../includes/functions.php';

// Check user role: Only admin allowed on this page
checkRole('admin');

$error = '';
$success = '';

// Handle user status changes (block, activate, delete)
if ($_POST && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    try {
        if ($action === 'block') {
            // Block user by setting status to 'blocked'
            $stmt = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User blocked successfully!";
        } elseif ($action === 'activate') {
            // Activate user by setting status to 'active'
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User activated successfully!";
        } elseif ($action === 'delete') {
            // Permanently delete user from database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all non-admin users ordered by creation date descending
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-cube me-2"></i>InventoryPro
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_orders.php">Orders</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page container -->
    <div class="container mt-4">
        <h1><i class="fas fa-users me-2"></i>Manage Users</h1>

        <!-- Display errors -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Display success -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Users Table Card -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted text-center py-4">No users found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['username'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-<?= $user['role'] === 'supplier' ? 'warning' : 'info' ?>"><?= ucfirst($user['role']) ?></span></td>
                                        <td><span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($user['status']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="changeStatus(<?= $user['id'] ?>, 'block')">
                                                    <i class="fas fa-ban"></i> Block
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="changeStatus(<?= $user['id'] ?>, 'activate')">
                                                    <i class="fas fa-check"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden form to submit block/activate/delete -->
    <form method="POST" id="actionForm" style="display:none;">
        <input type="hidden" name="user_id" id="action_user_id">
        <input type="hidden" name="action" id="action_type">
    </form>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function changeStatus(userId, action) {
            const message = action === 'block' ? 'block this user' : 'activate this user';
            if (confirm('Are you sure you want to ' + message + '?')) {
                document.getElementById('action_user_id').value = userId;
                document.getElementById('action_type').value = action;
                document.getElementById('actionForm').submit();
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                document.getElementById('action_user_id').value = userId;
                document.getElementById('action_type').value = 'delete';
                document.getElementById('actionForm').submit();
            }
        }
    </script>
</body>
</html>
