<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<form action="send_login_code.php" method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <button type="submit">Send Login Code</button>
</form>

</body>
</html>
