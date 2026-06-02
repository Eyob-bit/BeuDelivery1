<?php
/**
 * Setup Roles - Ensures all system roles exist
 * Run this once to set up the role system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

echo "<!DOCTYPE html><html><head><title>Setup Roles</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'><div class='container mt-5'><div class='row justify-content-center'><div class='col-md-8'>";

echo "<div class='card'><div class='card-body'>";
echo "<h2 class='card-title'>Setup System Roles</h2>";
echo "<p class='text-muted'>This will create all necessary roles for the system</p>";
echo "<hr>";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("<div class='alert alert-danger'>❌ Connection failed: " . mysqli_connect_error() . "</div>");
}

echo "<div class='alert alert-success'>✅ Connected to database: $database</div>";

// Check if roles table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'roles'");
if (mysqli_num_rows($check_table) == 0) {
    echo "<div class='alert alert-warning'>⚠️ Roles table doesn't exist. Creating...</div>";
    
    $create_roles = "CREATE TABLE `roles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_roles)) {
        echo "<div class='alert alert-success'>✅ Roles table created</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating roles table: " . mysqli_error($conn) . "</div>";
    }
}

// Check if user_roles table exists
$check_user_roles = mysqli_query($conn, "SHOW TABLES LIKE 'user_roles'");
if (mysqli_num_rows($check_user_roles) == 0) {
    echo "<div class='alert alert-warning'>⚠️ User_roles table doesn't exist. Creating...</div>";
    
    $create_user_roles = "CREATE TABLE `user_roles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `role_id` INT NOT NULL,
        `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `user_role_unique` (`user_id`, `role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_user_roles)) {
        echo "<div class='alert alert-success'>✅ User_roles table created</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creating user_roles table: " . mysqli_error($conn) . "</div>";
    }
}

// Define system roles
$roles = [
    ['name' => 'admin', 'description' => 'System Administrator - Full access to all features'],
    ['name' => 'merchant', 'description' => 'Store Owner - Manage store, menu, and orders'],
    ['name' => 'delivery', 'description' => 'Delivery Person - Deliver orders and track earnings'],
    ['name' => 'customer', 'description' => 'Regular Customer - Browse stores and place orders']
];

echo "<h5 class='mt-4'>Creating System Roles:</h5>";
echo "<div class='list-group mb-4'>";

$created = 0;
$existing = 0;

foreach ($roles as $role) {
    $name = $role['name'];
    $description = mysqli_real_escape_string($conn, $role['description']);
    
    // Check if role exists
    $check = mysqli_query($conn, "SELECT id FROM roles WHERE name = '$name'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "<div class='list-group-item'>";
        echo "<div class='d-flex w-100 justify-content-between'>";
        echo "<h6 class='mb-1'>✅ " . ucfirst($name) . "</h6>";
        echo "<small class='text-muted'>Already exists</small>";
        echo "</div>";
        echo "<p class='mb-1 small text-muted'>$description</p>";
        echo "</div>";
        $existing++;
    } else {
        // Create role
        $insert = "INSERT INTO roles (name, description) VALUES ('$name', '$description')";
        if (mysqli_query($conn, $insert)) {
            echo "<div class='list-group-item list-group-item-success'>";
            echo "<div class='d-flex w-100 justify-content-between'>";
            echo "<h6 class='mb-1'>✨ " . ucfirst($name) . "</h6>";
            echo "<small class='text-success'>Created</small>";
            echo "</div>";
            echo "<p class='mb-1 small text-muted'>$description</p>";
            echo "</div>";
            $created++;
        } else {
            echo "<div class='list-group-item list-group-item-danger'>";
            echo "<h6 class='mb-1'>❌ " . ucfirst($name) . "</h6>";
            echo "<p class='mb-1 small'>Error: " . mysqli_error($conn) . "</p>";
            echo "</div>";
        }
    }
}

echo "</div>";

// Summary
echo "<div class='alert alert-info'>";
echo "<h5>Summary:</h5>";
echo "<ul class='mb-0'>";
echo "<li>✨ Created: $created roles</li>";
echo "<li>✅ Already existed: $existing roles</li>";
echo "<li>📊 Total roles: " . ($created + $existing) . "</li>";
echo "</ul>";
echo "</div>";

// Show current roles
echo "<h5 class='mt-4'>Current Roles in Database:</h5>";
$all_roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY name");

if ($all_roles && mysqli_num_rows($all_roles) > 0) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Created</th></tr></thead>";
    echo "<tbody>";
    
    while ($role = mysqli_fetch_assoc($all_roles)) {
        echo "<tr>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td><strong>" . ucfirst($role['name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($role['description']) . "</td>";
        echo "<td>" . date('M j, Y', strtotime($role['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>No roles found in database</div>";
}

// Check for users with roles
echo "<h5 class='mt-4'>Users with Roles:</h5>";
$users_with_roles = mysqli_query($conn, "
    SELECT u.id, u.email, u.first_name, u.last_name, GROUP_CONCAT(r.name SEPARATOR ', ') as roles
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    GROUP BY u.id
    LIMIT 10
");

if ($users_with_roles && mysqli_num_rows($users_with_roles) > 0) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th></tr></thead>";
    echo "<tbody>";
    
    while ($user = mysqli_fetch_assoc($users_with_roles)) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td><span class='badge bg-primary'>" . htmlspecialchars($user['roles']) . "</span></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>No users have been assigned roles yet</div>";
}

mysqli_close($conn);

echo "</div></div>"; // card

// Next steps
echo "<div class='card mt-4'><div class='card-body'>";
echo "<h5 class='card-title'>✅ Setup Complete!</h5>";
echo "<p>All system roles are now ready. You can:</p>";
echo "<ol>";
echo "<li>Create an admin user: <a href='../admin/access_admin.php' class='btn btn-sm btn-primary'>Go to Admin Access</a></li>";
echo "<li>Test merchant registration: <a href='../merchant/getStarted.php' class='btn btn-sm btn-success'>Merchant Signup</a></li>";
echo "<li>Regular login: <a href='../auth/login.php' class='btn btn-sm btn-secondary'>Login Page</a></li>";
echo "</ol>";
echo "</div></div>";

echo "</div></div></div></body></html>";
?>
