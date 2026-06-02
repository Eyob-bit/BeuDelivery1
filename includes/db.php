<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "beu_delivery_v2";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if users table exists before trying to modify it
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");

if ($table_check && mysqli_num_rows($table_check) > 0) {
    // Table exists, check for missing columns
    $check_columns = mysqli_query($conn, "DESCRIBE users");
    
    if ($check_columns) {
        $existing_columns = [];
        while ($row = mysqli_fetch_assoc($check_columns)) {
            $existing_columns[$row['Field']] = true;
        }
        
        // Add missing columns if they don't exist
        if (!isset($existing_columns['agreed_to_terms'])) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN agreed_to_terms BOOLEAN DEFAULT FALSE");
        }
        
        if (!isset($existing_columns['password_hash'])) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL");
        }
    }
}
?>