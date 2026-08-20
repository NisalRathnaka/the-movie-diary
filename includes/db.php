<?php

// Function to parse .env file line-by-line
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

// Load .env from root directory
loadEnv(__DIR__ . '/../.env');

// Fallback to live hosting credentials if .env is absent
$host     = $_ENV['DB_HOST'] ?? 'sql306.infinityfree.com';
$username = $_ENV['DB_USER'] ?? 'if0_42623541';
$password = $_ENV['DB_PASS'] ?? 'Nisal2005163';
$database = $_ENV['DB_NAME'] ?? 'if0_42623541_blog_app';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($host, $username, $password, $database);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failure: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

?>