<?php
session_start();
include "includes/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$messageType = "error";

$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($email) || empty($password)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $message = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } else {
        $checkSql = "SELECT id FROM user WHERE email = ? OR username = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "ss", $email, $username);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);

            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $message = "An account with that email or username already exists.";
            } else {
                mysqli_stmt_close($checkStmt);

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO user (username, email, password) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPassword);

                    if (mysqli_stmt_execute($stmt)) {
                        $new_user_id = mysqli_stmt_insert_id($stmt);
                        mysqli_stmt_close($stmt);

                        session_regenerate_id(true);
                        $_SESSION["user_id"] = $new_user_id;
                        $_SESSION["username"] = $username;
                        $_SESSION["email"] = $email;
                        $_SESSION["role"] = "user";
                        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $message = "Registration failed. Please try again later.";
                    }
                } else {
                    $message = "Database query execution error.";
                }
            }

            if (isset($checkStmt) && $checkStmt !== false) {
                mysqli_stmt_close($checkStmt);
            }
        } else {
            $message = "An unexpected error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - The Movie Diary 🎬</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">The Movie Diary 🎬</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary active">Register</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <div class="form-card">
            <h2 style="margin-bottom: 20px; text-align: center;">Create Account</h2>

            <?php if (!empty($message)): ?>
                <p style="color: <?php echo $messageType === 'success' ? '#28a745' : 'var(--accent-red, #e63946)'; ?>; margin-bottom: 15px; text-align: center; font-weight: 600;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password (min. 8 characters)</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                    Register
                </button>
            </form>

            <p style="margin-top: 20px; text-align: center; color: var(--text-secondary, #6c757d); font-size: 0.9rem;">
                Already have an account? <a href="login.php" style="color: var(--accent-red, #e63946); font-weight: 600;">Login here</a>
            </p>
        </div>
    </main>

</body>

</html>