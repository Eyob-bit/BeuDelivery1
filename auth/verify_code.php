<?php
include "../includes/db.php";

$email = $_POST['email'] ?? $_GET['email'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // show verification form
    ?>
    <form method="POST" action="verify_code.php">
        <input type="hidden" name="email" value="<?php echo $email; ?>">
        <input type="text" name="code" placeholder="Enter verification code" required>
        <input type="text" name="first_name" placeholder="First name" required>
        <input type="text" name="last_name" placeholder="Last name" required>
        <input type="text" name="phone" placeholder="Phone (optional)">
        <button type="submit">Complete signup</button>
    </form>
    <?php
    exit;
}

// POST request → process verification
$code  = trim($_POST['code']);
$email = trim($_POST['email']);
$first = $_POST['first_name'];
$last  = $_POST['last_name'];
$phone = $_POST['phone'];


// check code
$check = mysqli_query($conn, "
    SELECT * FROM email_verifications
    WHERE email='$email'
    AND code='$code'
");

$row = mysqli_fetch_assoc($check);

if (!$row) {
    die("Invalid verification code");
}

if (strtotime($row['expires_at']) < time()) {
    die("Verification code expired");
}



if (mysqli_num_rows($check) === 0) {
    die("Invalid or expired code");
}

// create user
mysqli_query($conn, "
    INSERT INTO users (email, first_name, last_name, phone, is_verified)
    VALUES ('$email', '$first', '$last', '$phone', 1)
");

$user_id = mysqli_insert_id($conn);

// assign user role
$role = mysqli_query($conn, "SELECT id FROM roles WHERE name='user'");
$role_id = mysqli_fetch_assoc($role)['id'];

mysqli_query($conn, "
    INSERT INTO user_roles (user_id, role_id)
    VALUES ($user_id, $role_id)
");

// cleanup
mysqli_query($conn, "DELETE FROM email_verifications WHERE email='$email'");

// redirect to login
header("Location: login.php");
exit;
