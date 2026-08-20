<?php
session_start();
include "includes/db.php";

// 1. Enforce authentication
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// 2. Enforce POST request method to prevent CSRF via GET requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method Not Allowed. Deletions must be submitted via POST.";
    exit();
}

// 3. Validate CSRF token (ensure you set $_SESSION['csrf_token'] during session start or login)
if (!isset($_POST["csrf_token"]) || !isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
    http_response_code(403);
    echo "Invalid or missing CSRF token.";
    exit();
}

// 4. Validate post ID
if (!isset($_POST["id"]) || !filter_var($_POST["id"], FILTER_VALIDATE_INT)) {
    header("Location: dashboard.php");
    exit();
}

$blog_id = (int)$_POST["id"];
$user_id = (int)$_SESSION["user_id"];

// 5. Fetch the blog first to check ownership and retrieve image path
$fetch_sql = "SELECT image_url FROM blogpost WHERE id = ? AND user_id = ?";
$fetch_stmt = mysqli_prepare($conn, $fetch_sql);
mysqli_stmt_bind_param($fetch_stmt, "ii", $blog_id, $user_id);
mysqli_stmt_execute($fetch_stmt);
$result = mysqli_stmt_get_result($fetch_stmt);

if (mysqli_num_rows($result) === 1) {
    $blog = mysqli_fetch_assoc($result);
    $image_path = $blog["image_url"];
    mysqli_stmt_close($fetch_stmt);

    // 6. Delete the record from database
    $delete_sql = "DELETE FROM blogpost WHERE id = ? AND user_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "ii", $blog_id, $user_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        if (mysqli_stmt_affected_rows($delete_stmt) === 1) {
            // Delete image file if it exists locally
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }

            mysqli_stmt_close($delete_stmt);
            header("Location: dashboard.php");
            exit();
        } else {
            http_response_code(403);
            echo "You are not authorized to delete this blog.";
        }
    } else {
        http_response_code(500);
        echo "Failed to delete blog.";
    }

    mysqli_stmt_close($delete_stmt);
} else {
    mysqli_stmt_close($fetch_stmt);
    http_response_code(403);
    echo "You are not authorized to delete this blog or it does not exist.";
}
?>