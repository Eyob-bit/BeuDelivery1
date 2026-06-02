<?php
/**
 * Admin Access Helper
 * This script helps you quickly access the admin panel for testing
 * REMOVE THIS FILE IN PRODUCTION!
 */

session_start();
include "../includes/db.php";

echo "<!DOCTYPE html>
<html><head><title>Admin Access</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head><body class='bg-light'><div class='container mt-5'><div class='row justify-content-center'><div class='col-md-6'>";

// First, ensure all roles exist
$roles_to_create = [
    ['name' => 'admin', 'description' => 'System Administrator'],
    ['name' => 'merchant', 'description' => 'Store Owner/Merchant'],
    ['name' => 'delivery', 'description' => 'Delivery Person'],
    ['name' => 'customer', 'description' => 'Regular Customer']
];

foreach ($roles_to_create as $role) {
    mysqli_query($conn, "INSERT IGNORE INTO roles (name, description) VALUES ('{$role['name']}', '{$role['description']}')");
}

// Check if admin user exists
$check_admin = "SELECT u.*, GROUP_CONCAT(r.name) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE r.name = 'admin' 
                GROUP BY u.id 
                LIMIT 1";
$admin_result = mysqli_query($conn, $check_admin);

if (!$admin_result || mysqli_num_rows($admin_result) == 0) {
    echo "<div class='card'><div class='card-body'>";
    echo "<h3 class='card-title'>No Admin User Found</h3>";
    echo "<p>Let's create an admin user for you.</p>";
    
    // Create admin user
    $admin_email = "admin@beudelivery.com";
    $admin_phone = "0911111111";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    
    $create_user = "INSERT INTO users (email, phone, password_hash, first_name, last_name, user_type, created_at) 
                    VALUES ('$admin_email', '$admin_phone', '$admin_password', 'Admin', 'User', 'admin', NOW())";
    
    if (mysqli_query($conn, $create_user)) {
        $admin_id = mysqli_insert_id($conn);
        
        // Get admin role ID
        $role_result = mysqli_query($conn, "SELECT id FROM roles WHERE name = 'admin'");
        $role = mysqli_fetch_assoc($role_result);
        
        // Assign admin role
        mysqli_query($conn, "INSERT INTO user_roles (user_id, role_id) VALUES ('$admin_id', '{$role['id']}')");
        
        echo "<div class='alert alert-success'>";
        echo "<h5>✅ Admin User Created!</h5>";
        echo "<p><strong>Email:</strong> $admin_email<br>";
        echo "<strong>Phone:</strong> $admin_phone<br>";
        echo "<strong>Password:</strong> admin123</p>";
        echo "<p class='mt-3'><small class='text-muted'>You can login with either email or phone number</small></p>";
        echo "</div>";
        
        // Auto-login
        $_SESSION['user_id'] = $admin_id;
        $_SESSION['logged_in'] = true;
        $_SESSION['user_name'] = 'Admin User';
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_roles'] = ['admin'];
        
        echo "<a href='admin_panel.php' class='btn btn-primary btn-lg w-100'>Go to Admin Panel</a>";
        echo "<p class='mt-3'><a href='../auth/login.php' class='btn btn-outline-secondary w-100'>Or Login Normally</a></p>";
    } else {
        echo "<div class='alert alert-danger'>Error creating admin user: " . mysqli_error($conn) . "</div>";
    }
    
    echo "</div></div>";
} else {
    $admin = mysqli_fetch_assoc($admin_result);
    
    echo "<div class='card'><div class='card-body'>";
    echo "<h3 class='card-title'>Admin Access</h3>";
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_roles']) && in_array('admin', $_SESSION['user_roles'])) {
        echo "<div class='alert alert-success'>✅ You are already logged in as admin!</div>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($_SESSION['user_name']) . "</p>";
        echo "<a href='admin_panel.php' class='btn btn-primary btn-lg w-100 mb-2'>Go to Admin Panel</a>";
        echo "<a href='../auth/logout.php' class='btn btn-outline-danger w-100'>Logout</a>";
    } else {
        echo "<p>Admin user exists. You can login using:</p>";
        echo "<div class='alert alert-info'>";
        echo "<strong>Email:</strong> " . htmlspecialchars($admin['email']) . "<br>";
        echo "<strong>Phone:</strong> " . htmlspecialchars($admin['phone']) . "<br>";
        echo "<strong>Password:</strong> admin123 (default)";
        echo "</div>";
        
        // Auto-login button
        if (isset($_GET['auto_login'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_roles'] = explode(',', $admin['roles']);
            
            header("Location: admin_panel.php");
            exit();
        }
        
        echo "<a href='?auto_login=1' class='btn btn-primary btn-lg w-100 mb-2'>Quick Login as Admin</a>";
        echo "<a href='../auth/login.php' class='btn btn-outline-secondary w-100'>Use Regular Login Page</a>";
        echo "<p class='mt-3'><small class='text-muted'>Regular login supports email or phone number</small></p>";
    }
    
    echo "</div></div>";
}

// Show all roles created
echo "<div class='card mt-4'><div class='card-body'>";
echo "<h5 class='card-title'>System Roles</h5>";
echo "<p class='text-muted'>The following roles are available in the system:</p>";
echo "<ul class='list-group'>";
foreach ($roles_to_create as $role) {
    $check_role = mysqli_query($conn, "SELECT COUNT(*) as count FROM roles WHERE name = '{$role['name']}'");
    $role_exists = mysqli_fetch_assoc($check_role);
    $icon = $role_exists['count'] > 0 ? '✅' : '❌';
    echo "<li class='list-group-item'>";
    echo "$icon <strong>" . ucfirst($role['name']) . "</strong> - " . $role['description'];
    echo "</li>";
}
echo "</ul>";
echo "</div></div>";

echo "</div></div></div></body></html>";
?>
