<?php
$host     = "localhost";
$username = "root";
$password = "";          // Default XAMPP MySQL password is empty
$database = "blog_app";  // Must match your database name in phpMyAdmin

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>