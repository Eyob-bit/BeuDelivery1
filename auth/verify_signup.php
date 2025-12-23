<?php
include "../includes/db.php";

$email = $_POST['email'];

// generate 6-digit code
$code = rand(100000, 999999);
$expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// remove old codes
mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");

// save new code
mysqli_query($conn, "
    INSERT INTO email_verifications (email, code, expires_at)
    VALUES ('$email', '$code', '$expires')
");

echo "<h3>Verification code sent</h3>";
echo "<p><strong>DEV MODE CODE:</strong> $code</p>";
echo "<a href='verify_code.php?email=$email'>Continue</a>";
