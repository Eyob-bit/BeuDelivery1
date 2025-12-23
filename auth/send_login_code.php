<?php
include "../includes/db.php";

$email = trim($_POST['email']);
$code  = rand(100000, 999999);
$expires = date("Y-m-d H:i:s", time() + 600);

// check if user exists
$checkUser = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($checkUser) == 0) {
    die("No account found");
}

// remove old login codes
mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");

mysqli_query($conn, "
    INSERT INTO email_verifications (email, code, expires_at)
    VALUES ('$email', '$code', '$expires')
");

echo "DEV LOGIN CODE: $code";

echo "<form action='verify_login.php' method='POST'>
        <input type='hidden' name='email' value='$email'>
        <input type='text' name='code' placeholder='Enter code' required>
        <button>Login</button>
      </form>";
