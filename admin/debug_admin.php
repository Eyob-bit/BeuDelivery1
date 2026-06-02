<?php
session_start();
include "includes/db.php";

echo "<h3>Debug Admin Access</h3>";
echo "<pre>";

echo "Session data:\n";
print_r($_SESSION);

echo "\n\nChecking user ID in session: ";
if (isset($_SESSION['user_id'])) {
    echo $_SESSION['user_id'];
    
    // Check if user exists
    $user_sql = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
    $user_result = mysqli_query($conn, $user_sql);
    
    if (mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        echo "\nUser found: " . $user['email'];
        
        // Check user roles
        $role_sql = "SELECT r.name FROM user_roles ur 
                     JOIN roles r ON ur.role_id = r.id 
                     WHERE ur.user_id = '{$_SESSION['user_id']}'";
        $role_result = mysqli_query($conn, $role_sql);
        
        echo "\nUser roles: ";
        $roles = [];
        while($role = mysqli_fetch_assoc($role_result)) {
            $roles[] = $role['name'];
        }
        echo empty($roles) ? "No roles" : implode(", ", $roles);
        
        // Check if admin
        if (in_array('admin', $roles)) {
            echo "\n✓ User is an ADMIN";
        } else {
            echo "\n✗ User is NOT an admin";
        }
    } else {
        echo "\n✗ User not found in database";
    }
} else {
    echo "No user ID in session";
}

echo "\n\nCookies:\n";
print_r($_COOKIE);

echo "</pre>";
?>