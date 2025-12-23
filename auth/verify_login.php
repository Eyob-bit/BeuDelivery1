<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "VERIFY LOGIN REACHED<br>";

include "../includes/db.php";

$email = trim($_POST['email']);
$code  = trim($_POST['code']);

$check = mysqli_query($conn, "
    SELECT * FROM email_verifications
    WHERE email='$email'
    AND code='$code'
");

$row = mysqli_fetch_assoc($check);

if (!$row) {
    die("Invalid login code");
}

if (strtotime($row['expires_at']) < time()) {
    die("Login code expired");
}

// fetch user
$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE email='$email'")
);

// set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['email']   = $email;

// update login time
mysqli_query($conn, "
    UPDATE users SET last_login = NOW() WHERE id='{$user['id']}'
");

// cleanup
$roleQuery = mysqli_query($conn, "
    SELECT r.name AS role
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = '{$user['id']}'
    LIMIT 1
");

if (!$roleQuery) {
    die("ROLE QUERY FAILED: " . mysqli_error($conn));
}

$roleRow = mysqli_fetch_assoc($roleQuery);
$role = $roleRow['role'] ?? 'user';




if ($role == 'admin') {
    header("Location: ../admin/dashboard.php");
} elseif ($role == 'restaurant_owner') {
    header("Location: ../restaurant/dashboard.php");
} else {
    header("Location: ../user/home.php");
}
exit;
