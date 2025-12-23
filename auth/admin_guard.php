<?php
include "../includes/db.php";

$user_id = $_SESSION['user_id'];

$res = mysqli_query($conn, "
    SELECT r.name FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id='$user_id' AND r.name='admin'
");

if (mysqli_num_rows($res) == 0) {
    die("Admin only");
}
