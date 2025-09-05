<?php
require_once 'config.php';

try {
    echo "Testing database connection...<br>";
    
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful!<br><br>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@admin.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found: " . $admin['name'] . "<br>";
        echo "Password hash in DB: " . substr($admin['password'], 0, 20) . "...<br>";
        
        // Test password verification
        if (password_verify('password', $admin['password'])) {
            echo "✅ Password verification works!<br>";
        } else {
            echo "❌ Password verification failed!<br>";
        }
    } else {
        echo "❌ Admin user not found. Creating one...<br>";
        
        // Create admin user
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin', 'admin@admin.com', $hashed_password, 'admin']);
        echo "✅ Admin user created!<br>";
    }
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<br>📋 Users table structure:<br>";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        
        // Check password column size
        if ($col['Field'] === 'password' && strpos($col['Type'], 'varchar(255)') === false) {
            echo "  ⚠️ WARNING: Password column should be VARCHAR(255)!<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
<?php
require_once 'config.php';

try {
    echo "Testing database connection...<br>";
    
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful!<br><br>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@admin.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found: " . $admin['name'] . "<br>";
        echo "Password hash in DB: " . substr($admin['password'], 0, 20) . "...<br>";
        
        // Test password verification
        if (password_verify('password', $admin['password'])) {
            echo "✅ Password verification works!<br>";
        } else {
            echo "❌ Password verification failed!<br>";
        }
    } else {
        echo "❌ Admin user not found. Creating one...<br>";
        
        // Create admin user
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin', 'admin@admin.com', $hashed_password, 'admin']);
        echo "✅ Admin user created!<br>";
    }
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<br>📋 Users table structure:<br>";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        
        // Check password column size
        if ($col['Field'] === 'password' && strpos($col['Type'], 'varchar(255)') === false) {
            echo "  ⚠️ WARNING: Password column should be VARCHAR(255)!<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
