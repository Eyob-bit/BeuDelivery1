<?php
$conn = mysqli_connect("localhost", "root", "", "beu_delivery_v2");

if (!$conn) {
    die("DB connection failed");
}
