<?php
session_start();
include "includes/db.php";

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $sql = "SELECT id, username, email, password, role FROM user WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && $user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"] ?? "user";
                    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                    mysqli_stmt_close($stmt);
                    header("Location: dashboard.php");
                    exit();
                }
            }

            $message = "Invalid email or password.";
            mysqli_stmt_close($stmt);
        } else {
            $message = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login - The Movie Diary 🎬</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">The Movie Diary 🎬</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php" class="active">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Register</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <div class="form-card">
            <h2 style="margin-bottom: 20px; text-align: center;">Welcome Back</h2>

            <?php if (!empty($message)): ?>
                <p style="color: var(--accent-red, #e63946); margin-bottom: 15px; text-align: center; font-weight: 600;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                    Login
                </button>
            </form>

            <p style="margin-top: 20px; text-align: center; color: var(--text-secondary, #6c757d); font-size: 0.9rem;">
                Don't have an account? <a href="register.php" style="color: var(--accent-red, #e63946); font-weight: 600;">Register here</a>
            </p>
        </div>
    </main>

</body>

</html>