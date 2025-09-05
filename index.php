<?php
// Load the application configuration file that likely contains database credentials and other settings
require_once 'config.php';

// Include a file with custom functions (e.g., isLoggedIn() might be defined here)
require_once 'includes/functions.php';

// If user is already logged in, redirect them to the dashboard appropriate for their role
if (isLoggedIn()) {
    // Check the user's role stored in session
    switch ($_SESSION['role']) {
        case 'admin':
            // Admins go to the admin dashboard
            header('Location: admin/dashboard.php');
            break;
        case 'supplier':
            // Suppliers go to the supplier dashboard
            header('Location: supplier/dashboard.php');
            break;
        case 'buyer':
            // Buyers go to the buyer dashboard
            header('Location: buyer/dashboard.php');
            break;
    }
    // Terminate script execution to prevent further processing after redirect
    exit();
}

// Fetch statistics to display on the homepage: total products, suppliers, and buyers
try {
    // Count total products currently in stock (quantity > 0)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity > 0");
    $total_products = $stmt->fetchColumn();
    
    // Count active suppliers (users with role 'supplier' and status 'active')
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supplier' AND status = 'active'");
    $total_suppliers = $stmt->fetchColumn();
    
    // Count active buyers (users with role 'buyer' and status 'active')
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND status = 'active'");
    $total_buyers = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // If there's a database error, set all stats to zero to prevent page breakage
    $total_products = 0;
    $total_suppliers = 0;
    $total_buyers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoryPro - Professional Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --admin-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --supplier-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --buyer-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
            --shadow-light: 0 5px 15px rgba(0,0,0,0.08);
            --shadow-medium: 0 10px 30px rgba(0,0,0,0.12);
            --shadow-heavy: 0 20px 60px rgba(0,0,0,0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            margin: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 80px 0 40px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
        }

        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            position: relative;
        }

        .content-left {
            padding: 60px 50px;
            position: relative;
        }

        .content-right {
            background: linear-gradient(135deg, #f8f9ff 0%, #e9ecff 100%);
            padding: 60px 50px;
            position: relative;
        }

        .brand-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .brand-icon {
            width: 100px;
            height: 100px;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: var(--shadow-medium);
            transform: rotate(-5deg);
        }

        .brand-icon i {
            font-size: 3rem;
            color: white;
        }

        .brand-title {
            font-size: 4rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 1.3rem;
            color: var(--text-muted);
            font-weight: 400;
            max-width: 500px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 50px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .feature-content h6 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .feature-content small {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .action-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 40px;
        }

        .btn-custom {
            padding: 18px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
            min-width: 250px;
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            color: white;
            margin-bottom: 20px;
        }

        .btn-success-custom {
            background: var(--supplier-gradient);
            color: white;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-muted);
            font-weight: 500;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(0,0,0,0.1);
        }

        .divider span {
            padding: 0 20px;
        }

        .role-showcase {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 40px 0;
        }

        .role-item {
            text-align: center;
            padding: 25px 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .role-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .role-icon-small {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 15px;
        }

        .role-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .role-description {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .footer-note {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .footer-note small {
            color: var(--text-muted);
            line-height: 1.6;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 0;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            color: white !important;
        }

        /* Modal Improvements */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-heavy);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-medium);
        }

        .role-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-8px);
        }

        .role-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #28a745;
            color: white;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }

        .role-card.selected .role-badge {
            display: flex;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-left,
            .content-right {
                padding: 40px 30px;
            }
            
            .brand-title {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 992px) {
            .main-card .row {
                flex-direction: column;
            }
            
            .content-right {
                background: rgba(255, 255, 255, 0.95);
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .role-showcase {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0 20px;
            }
            
            .content-left,
            .content-right {
                padding: 30px 25px;
            }
            
            .brand-title {
                font-size: 2.8rem;
            }
            
            .brand-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin: 30px 0;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .btn-custom {
                padding: 15px 30px;
                font-size: 1rem;
                min-width: 200px;
            }
        }

        @media (max-width: 576px) {
            .brand-icon {
                width: 80px;
                height: 80px;
            }
            
            .brand-icon i {
                font-size: 2.5rem;
            }
            
            .brand-title {
                font-size: 2.2rem;
            }
            
            .brand-subtitle {
                font-size: 1rem;
            }
            
            .btn-custom {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .feature-item {
                padding: 15px;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
                margin-right: 15px;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        .slide-up {
            animation: slideUp 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading States */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cube me-2"></i>
                InventoryPro
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11 col-lg-12">
                    <div class="main-card fade-in">
                        <div class="row g-0">
                            <!-- Left Side - Branding & Features -->
                            <div class="col-lg-7">
                                <div class="content-left">
                                    <!-- Brand Section -->
                                    <div class="brand-section slide-up">
                                        <div class="brand-icon">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <h1 class="brand-title">InventoryPro</h1>
                                        <p class="brand-subtitle">
                                            Professional Inventory Management System for Modern Businesses
                                        </p>
                                    </div>

                                    <!-- Statistics -->
                                    <div class="stats-grid slide-up">
                                        <div class="stat-item">
                                            <div class="stat-number text-primary"><?php echo number_format($total_products); ?></div>
                                            <div class="stat-label">Products</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number text-success"><?php echo number_format($total_suppliers); ?></div>
                                            <div class="stat-label">Suppliers</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-number text-info"><?php echo number_format($total_buyers); ?></div>
                                            <div class="stat-label">Buyers</div>
                                        </div>
                                    </div>

                                    <!-- Features -->
                                    <div class="features-grid slide-up">
                                        <div class="feature-item">
                                            <div class="feature-icon" style="background: var(--admin-gradient);">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <div class="feature-content">
                                                <h6>Enterprise Security</h6>
                                                <small>Advanced encryption and secure access controls</small>
                                            </div>
                                        </div>
                                        <div class="feature-item">
                                            <div class="feature-icon" style="background: var(--supplier-gradient);">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="feature-content">
                                                <h6>Fully Responsive</h6>
                                                <small>Perfect experience on all devices</small>
                                            </div>
                                        </div>
                                        <div class="feature-item">
                                            <div class="feature-icon" style="background: var(--buyer-gradient);">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div class="feature-content">
                                                <h6>Real-time Analytics</h6>
                                                <small>Comprehensive reports and insights</small>
                                            </div>
                                        </div>
                                        <div class="feature-item">
                                            <div class="feature-icon" style="background: var(--admin-gradient);">
                                                <i class="fas fa-cloud"></i>
                                            </div>
                                            <div class="feature-content">
                                                <h6>Cloud Integration</h6>
                                                <small>Access your data from anywhere</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side - Actions -->
                            <div class="col-lg-5">
                                <div class="content-right">
                                    <div class="action-section slide-up">
                                        <h2 class="section-title">Get Started</h2>
                                        <p class="section-subtitle">Join thousands of businesses using InventoryPro</p>

                                        <!-- Main Action Buttons -->
                                        <div class="d-grid gap-3">
                                            <button class="btn btn-custom btn-primary-custom" onclick="showLoginModal()">
                                                <i class="fas fa-sign-in-alt me-2"></i>
                                                Login to Your Account
                                            </button>
                                            
                                            <div class="divider">
                                                <span>OR</span>
                                            </div>
                                            
                                            <button class="btn btn-custom btn-success-custom" onclick="showRegisterModal()">
                                                <i class="fas fa-user-plus me-2"></i>
                                                Create New Account
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Role Showcase -->
                                    <div class="role-showcase slide-up">
                                        <div class="role-item">
                                            <div class="role-icon-small" style="background: var(--admin-gradient);">
                                                <i class="fas fa-cog"></i>
                                            </div>
                                            <div class="role-title">Admin Panel</div>
                                            <div class="role-description">Complete system control and management</div>
                                        </div>
                                        <div class="role-item">
                                            <div class="role-icon-small" style="background: var(--supplier-gradient);">
                                                <i class="fas fa-truck"></i>
                                            </div>
                                            <div class="role-title">Supplier Hub</div>
                                            <div class="role-description">Manage products and inventory</div>
                                        </div>
                                        <div class="role-item">
                                            <div class="role-icon-small" style="background: var(--buyer-gradient);">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                            <div class="role-title">Buyer Portal</div>
                                            <div class="role-description">Browse and purchase products</div>
                                        </div>
                                    </div>

                                    <!-- Footer Note -->
                                    <div class="footer-note slide-up">
                                        <small>
                                            <strong>Choose your role during registration:</strong><br>
                                            <span class="text-primary">Administrator</span> • 
                                            <span class="text-success">Supplier</span> • 
                                            <span class="text-info">Buyer</span><br>
                                            <em>All roles have full access to their respective features</em>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login to Your Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="loginAlert"></div>
                    <form id="loginForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email or Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" name="login_field" placeholder="Enter your email or username" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            <span class="loading-text">Login</span>
                        </button>
                    </form>

                    <!-- Demo Admin Credentials -->
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <small class="text-muted">
                            <strong class="text-dark">Demo Admin Account:</strong><br>
                            <span class="text-primary">Email:</span> admin@admin.com<br>
                            <span class="text-primary">Password:</span> password
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <small class="text-muted">
                        Don't have an account? 
                        <a href="#" onclick="switchToRegister()" class="text-primary fw-semibold">Create one here</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Create Your Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="registerAlert"></div>
                    
                    <!-- Step 1: Role Selection -->
                    <div id="roleSelection" class="step-content">
                        <h6 class="mb-4 text-center fw-semibold">Choose Your Role</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="role-card p-4 text-center" data-role="admin" onclick="selectRole('admin')">
                                    <div class="role-badge">✓</div>
                                    <div class="role-icon-small mx-auto mb-3" style="background: var(--admin-gradient); width: 70px; height: 70px; font-size: 1.8rem;">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <h6 class="fw-semibold">Administrator</h6>
                                    <small class="text-muted">Full system control and management</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="role-card p-4 text-center" data-role="supplier" onclick="selectRole('supplier')">
                                    <div class="role-badge">✓</div>
                                    <div class="role-icon-small mx-auto mb-3" style="background: var(--supplier-gradient); width: 70px; height: 70px; font-size: 1.8rem;">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <h6 class="fw-semibold">Supplier</h6>
                                    <small class="text-muted">Manage products & inventory</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="role-card p-4 text-center" data-role="buyer" onclick="selectRole('buyer')">
                                    <div class="role-badge">✓</div>
                                    <div class="role-icon-small mx-auto mb-3" style="background: var(--buyer-gradient); width: 70px; height: 70px; font-size: 1.8rem;">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <h6 class="fw-semibold">Buyer</h6>
                                    <small class="text-muted">Browse & purchase products</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <button class="btn btn-primary btn-custom" onclick="proceedToForm()" disabled id="proceedBtn">
                                <i class="fas fa-arrow-right me-2"></i>Continue Registration
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Registration Form -->
                    <div id="registrationForm" class="step-content">
                        <div class="d-flex align-items-center mb-4">
                            <button class="btn btn-sm btn-outline-secondary me-3" onclick="backToRoleSelection()">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <h6 class="mb-0 fw-semibold">Complete Your Registration</h6>
                        </div>
                        
                        <form id="registerForm" class="needs-validation" novalidate>
                            <input type="hidden" id="selectedRole" name="role">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Full Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3" id="usernameField">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" class="form-control" name="username">
                                </div>
                                <div class="col-md-6 mb-3" id="companyField" style="display: none;">
                                    <label class="form-label fw-semibold">Company Name</label>
                                    <input type="text" class="form-control" name="company_name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Password *</label>
                                    <input type="password" class="form-control" name="password" id="registerPassword" required>
                                    <div id="passwordStrength" class="mt-2"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Confirm Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Enter your address (optional)"></textarea>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                <label class="form-check-label" for="agreeTerms">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                                    <a href="#" class="text-primary">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 btn-custom">
                                <i class="fas fa-user-plus me-2"></i>
                                <span class="loading-text">Create Account</span>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <small class="text-muted">
                        Already have an account? 
                        <a href="#" onclick="switchToLogin()" class="text-primary fw-semibold">Login here</a>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="successToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body fw-semibold"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let loginModal, registerModal, successToast;
        let selectedUserRole = '';

        document.addEventListener('DOMContentLoaded', function() {
            loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            successToast = new bootstrap.Toast(document.getElementById('successToast'));

            // Form submissions
            document.getElementById('loginForm').addEventListener('submit', handleLogin);
            document.getElementById('registerForm').addEventListener('submit', handleRegister);

            // Password strength
            document.getElementById('registerPassword').addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Add entrance animations with delay
            setTimeout(() => {
                document.querySelectorAll('.slide-up').forEach((el, index) => {
                    el.style.animationDelay = `${index * 0.1}s`;
                });
            }, 100);
        });

        function showLoginModal() {
            loginModal.show();
        }

        function showRegisterModal() {
            // Reset to role selection step
            document.getElementById('roleSelection').classList.add('active');
            document.getElementById('registrationForm').classList.remove('active');
            selectedUserRole = '';
            document.querySelectorAll('.role-card').forEach(card => card.classList.remove('selected'));
            document.getElementById('proceedBtn').disabled = true;
            registerModal.show();
        }

        function selectRole(role) {
            selectedUserRole = role;
            
            // Update UI
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector(`[data-role="${role}"]`).classList.add('selected');
            document.getElementById('proceedBtn').disabled = false;
        }

        function proceedToForm() {
            if (!selectedUserRole) return;
            
            // Show form step
            document.getElementById('roleSelection').classList.remove('active');
            document.getElementById('registrationForm').classList.add('active');
            
            // Set hidden role field
            document.getElementById('selectedRole').value = selectedUserRole;
            
            // Show/hide role-specific fields
            if (selectedUserRole === 'buyer') {
                document.getElementById('usernameField').style.display = 'block';
                document.querySelector('input[name="username"]').required = true;
                document.getElementById('companyField').style.display = 'none';
            } else if (selectedUserRole === 'supplier') {
                document.getElementById('usernameField').style.display = 'none';
                document.querySelector('input[name="username"]').required = false;
                document.getElementById('companyField').style.display = 'block';
            } else {
                document.getElementById('usernameField').style.display = 'none';
                document.querySelector('input[name="username"]').required = false;
                document.getElementById('companyField').style.display = 'none';
            }
        }

        function backToRoleSelection() {
            document.getElementById('registrationForm').classList.remove('active');
            document.getElementById('roleSelection').classList.add('active');
        }

        function switchToRegister() {
            loginModal.hide();
            setTimeout(() => showRegisterModal(), 300);
        }

        function switchToLogin() {
            registerModal.hide();
            setTimeout(() => showLoginModal(), 300);
        }

        async function handleLogin(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const loadingText = submitBtn.querySelector('.loading-text');
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            loadingText.innerHTML = '<span class="loading-spinner me-2"></span>Logging in...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(form);
                const response = await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: { 'X-Action': 'login' },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast('Login successful! Redirecting to your dashboard...');
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    document.getElementById('loginAlert').innerHTML = 
                        `<div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>${result.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`;
                }
            } catch (error) {
                document.getElementById('loginAlert').innerHTML = 
                    '<div class="alert alert-danger">Login failed. Please try again.</div>';
            } finally {
                loadingText.textContent = 'Login';
                submitBtn.disabled = false;
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const loadingText = submitBtn.querySelector('.loading-text');
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            // Password match check
            const password = form.querySelector('input[name="password"]').value;
            const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                document.getElementById('registerAlert').innerHTML = 
                    '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Passwords do not match.</div>';
                return;
            }
            
            loadingText.innerHTML = '<span class="loading-spinner me-2"></span>Creating account...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(form);
                const response = await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: { 'X-Action': 'register' },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast('Registration successful! Please login with your credentials.');
                    setTimeout(() => {
                        registerModal.hide();
                        setTimeout(() => showLoginModal(), 500);
                    }, 2000);
                } else {
                    document.getElementById('registerAlert').innerHTML = 
                        `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${result.message}</div>`;
                }
            } catch (error) {
                document.getElementById('registerAlert').innerHTML = 
                    '<div class="alert alert-danger">Registration failed. Please try again.</div>';
            } finally {
                loadingText.textContent = 'Create Account';
                submitBtn.disabled = false;
            }
        }

        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            if (!password) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            const checks = [
                /.{8,}/, /[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/
            ];
            
            checks.forEach(check => {
                if (check.test(password)) strength++;
            });
            
            const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['danger', 'warning', 'info', 'primary', 'success'];
            const level = Math.min(strength, 4);
            
            strengthDiv.innerHTML = `
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-${colors[level]}" 
                         style="width: ${(level + 1) * 20}%" 
                         role="progressbar"></div>
                </div>
                <small class="text-${colors[level]} fw-semibold mt-1 d-block">
                    Password strength: ${levels[level]}
                </small>
            `;
        }

        function showSuccessToast(message) {
            document.querySelector('#successToast .toast-body').textContent = message;
            successToast.show();
        }

        // Initialize role selection
        document.getElementById('roleSelection').classList.add('active');
    </script>

    <style>
        .step-content { display: none; }
        .step-content.active { display: block; }
    </style>
</body>
</html>
