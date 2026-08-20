<?php

// Function to parse .env file line-by-line locally
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Try loading local .env
loadEnv(__DIR__ . '/../.env');

// Set connection details
$host     = $_ENV['DB_HOST'] ?? 'sql306.infinityfree.com';
$username = $_ENV['DB_USER'] ?? 'if0_42623541';
$password = $_ENV['DB_PASS'] ?? 'Nisal2005163'; // Put your live password here
$database = $_ENV['DB_NAME'] ?? 'if0_42623541_blog_app';

// Disable strict exception throwing to avoid 500 crashes
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt database connection
$conn = @mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database Connection Error: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>