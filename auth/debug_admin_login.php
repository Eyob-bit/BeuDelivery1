<?php
/**
 * Debug Admin Login
 * Check if admin user exists and has correct roles
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../includes/db.php";

echo "<!DOCTYPE html><html><head><title>Debug Admin Login</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'><div class='container mt-5'><div class='row justify-content-center'><div class='col-md-10'>";

echo "<div class='card'><div class='card-body'>";
echo "<h2 class='card-title'>🔍 Admin Login Diagnostic</h2>";
echo "<p class='text-muted'>Checking admin user and roles...</p>";
echo "<hr>";

// Check if admin user exists
echo "<h5>Step 1: Check if admin user exists</h5>";
$admin_email = "admin@beudelivery.com";
$check_user = mysqli_query($conn, "SELECT * FROM users WHERE email = '$admin_email'");

if (!$check_user || mysqli_num_rows($check_user) == 0) {
    echo "<div class='alert alert-danger'>";
    echo "❌ Admin user does NOT exist!<br>";
    echo "<strong>Solution:</strong> Visit <a href='../admin/access_admin.php' class='alert-link'>admin/access_admin.php</a> to create admin user";
    echo "</div>";
} else {
    $admin_user = mysqli_fetch_assoc($check_user);
    echo "<div class='alert alert-success'>";
    echo "✅ Admin user exists!<br>";
    echo "<strong>ID:</strong> {$admin_user['id']}<br>";
    echo "<strong>Email:</strong> {$admin_user['email']}<br>";
    echo "<strong>Name:</strong> {$admin_user['first_name']} {$admin_user['last_name']}<br>";
    echo "<strong>User Type:</strong> {$admin_user['user_type']}<br>";
    echo "<strong>Created:</strong> {$admin_user['created_at']}";
    echo "</div>";
    
    $user_id = $admin_user['id'];
    
    // Check roles table
    echo "<h5>Step 2: Check if roles table exists</h5>";
    $check_roles_table = mysqli_query($conn, "SHOW TABLES LIKE 'roles'");
    
    if (mysqli_num_rows($check_roles_table) == 0) {
        echo "<div class='alert alert-danger'>";
        echo "❌ Roles table does NOT exist!<br>";
        echo "<strong>Solution:</strong> Visit <a href='../database/setup_roles.php' class='alert-link'>database/setup_roles.php</a> to create roles";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>✅ Roles table exists</div>";
        
        // Check if admin role exists
        echo "<h5>Step 3: Check if admin role exists</h5>";
        $check_admin_role = mysqli_query($conn, "SELECT * FROM roles WHERE name = 'admin'");
        
        if (!$check_admin_role || mysqli_num_rows($check_admin_role) == 0) {
            echo "<div class='alert alert-danger'>";
            echo "❌ Admin role does NOT exist!<br>";
            echo "<strong>Solution:</strong> Visit <a href='../database/setup_roles.php' class='alert-link'>database/setup_roles.php</a> to create roles";
            echo "</div>";
        } else {
            $admin_role = mysqli_fetch_assoc($check_admin_role);
            echo "<div class='alert alert-success'>";
            echo "✅ Admin role exists!<br>";
            echo "<strong>Role ID:</strong> {$admin_role['id']}<br>";
            echo "<strong>Name:</strong> {$admin_role['name']}<br>";
            echo "<strong>Description:</strong> {$admin_role['description']}";
            echo "</div>";
            
            $role_id = $admin_role['id'];
            
            // Check if user has admin role assigned
            echo "<h5>Step 4: Check if admin user has admin role assigned</h5>";
            $check_user_role = mysqli_query($conn, "
                SELECT ur.*, r.name as role_name 
                FROM user_roles ur 
                JOIN roles r ON ur.role_id = r.id 
                WHERE ur.user_id = '$user_id' AND r.name = 'admin'
            ");
            
            if (!$check_user_role || mysqli_num_rows($check_user_role) == 0) {
                echo "<div class='alert alert-warning'>";
                echo "⚠️ Admin user does NOT have admin role assigned!<br>";
                echo "<strong>Fixing now...</strong>";
                echo "</div>";
                
                // Assign admin role
                $assign_role = mysqli_query($conn, "
                    INSERT IGNORE INTO user_roles (user_id, role_id) 
                    VALUES ('$user_id', '$role_id')
                ");
                
                if ($assign_role) {
                    echo "<div class='alert alert-success'>";
                    echo "✅ Admin role assigned successfully!";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-danger'>";
                    echo "❌ Failed to assign admin role: " . mysqli_error($conn);
                    echo "</div>";
                }
            } else {
                echo "<div class='alert alert-success'>";
                echo "✅ Admin user has admin role assigned!";
                echo "</div>";
            }
            
            // Show all roles for this user
            echo "<h5>Step 5: All roles for admin user</h5>";
            $all_user_roles = mysqli_query($conn, "
                SELECT r.name, r.description, ur.assigned_at 
                FROM user_roles ur 
                JOIN roles r ON ur.role_id = r.id 
                WHERE ur.user_id = '$user_id'
            ");
            
            if ($all_user_roles && mysqli_num_rows($all_user_roles) > 0) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm'>";
                echo "<thead><tr><th>Role</th><th>Description</th><th>Assigned</th></tr></thead>";
                echo "<tbody>";
                
                while ($role = mysqli_fetch_assoc($all_user_roles)) {
                    echo "<tr>";
                    echo "<td><span class='badge bg-primary'>{$role['name']}</span></td>";
                    echo "<td>{$role['description']}</td>";
                    echo "<td>" . date('M j, Y', strtotime($role['assigned_at'])) . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-warning'>No roles assigned to this user</div>";
            }
        }
    }
    
    // Test login query
    echo "<h5>Step 6: Test login query</h5>";
    $test_query = "SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as roles
                   FROM users u
                   LEFT JOIN user_roles ur ON u.id = ur.user_id
                   LEFT JOIN roles r ON ur.role_id = r.id
                   WHERE u.email = '$admin_email'
                   GROUP BY u.id";
    
    $test_result = mysqli_query($conn, $test_query);
    
    if ($test_result && mysqli_num_rows($test_result) > 0) {
        $test_user = mysqli_fetch_assoc($test_result);
        echo "<div class='alert alert-success'>";
        echo "✅ Login query works!<br>";
        echo "<strong>User ID:</strong> {$test_user['id']}<br>";
        echo "<strong>Email:</strong> {$test_user['email']}<br>";
        echo "<strong>User Type:</strong> {$test_user['user_type']}<br>";
        echo "<strong>Roles:</strong> " . ($test_user['roles'] ? $test_user['roles'] : 'None') . "<br>";
        
        $roles_array = !empty($test_user['roles']) ? explode(', ', $test_user['roles']) : [];
        echo "<strong>Roles Array:</strong> " . json_encode($roles_array) . "<br>";
        echo "<strong>Has Admin Role:</strong> " . (in_array('admin', $roles_array) ? 'YES ✅' : 'NO ❌');
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Login query failed!</div>";
    }
}

echo "</div></div>"; // card

// Summary and next steps
echo "<div class='card mt-4'><div class='card-body'>";
echo "<h5 class='card-title'>Next Steps</h5>";
echo "<div class='d-grid gap-2'>";
echo "<a href='../admin/access_admin.php' class='btn btn-primary'>Create/Fix Admin User</a>";
echo "<a href='../database/setup_roles.php' class='btn btn-success'>Setup Roles</a>";
echo "<a href='login.php' class='btn btn-secondary'>Try Login Again</a>";
echo "</div>";
echo "</div></div>";

echo "</div></div></div></body></html>";
?>
