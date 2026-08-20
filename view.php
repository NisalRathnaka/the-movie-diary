<?php
session_start();
include "includes/db.php";

// Ensure CSRF token exists in session for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if blog ID exists and is valid
if (!isset($_GET["id"]) || !filter_var($_GET["id"], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET["id"];

// Get the blog post with author username
$sql = "SELECT blogPost.*, user.username
        FROM blogPost
        JOIN user ON blogPost.user_id = user.id
        WHERE blogPost.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo "Blog entry not found.";
    mysqli_stmt_close($stmt);
    exit();
}

$blog = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog["title"]); ?> - The Movie Diary 🎬</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">The Movie Diary 🎬</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="create.php" class="btn btn-primary">+ New Post</a></li>
                    <li><a href="logout.php" class="btn btn-secondary">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <main class="container">
        <div style="margin-bottom: 20px;">
            <a href="index.php" style="color: var(--text-secondary); font-size: 0.9rem;">← Back to Home</a>
        </div>

        <article class="card" style="padding: 0; overflow: hidden;">
            <?php if (!empty($blog['image_url']) && file_exists($blog['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                     style="width: 100%; max-height: 450px; object-fit: cover; display: block;">
            <?php endif; ?>

            <div style="padding: 35px;">
                <h1 style="font-size: 2.2rem; margin-bottom: 15px; color: var(--text-primary);">
                    <?php echo htmlspecialchars($blog["title"]); ?>
                </h1>

                <div class="card-meta" style="font-size: 0.95rem; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    By <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($blog["username"]); ?></strong> • Published on <?php echo date('F j, Y', strtotime($blog["created_at"])); ?>
                </div>

                <div style="font-size: 1.1rem; line-height: 1.8; color: #e0e0e0; min-height: 150px;">
                    <?php echo nl2br(htmlspecialchars($blog["content"])); ?>
                </div>

                <?php if (isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] === (int)$blog["user_id"]): ?>
                    <div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 15px; align-items: center;">
                        <a href="edit.php?id=<?php echo urlencode($blog["id"]); ?>" class="btn btn-secondary">
                            Edit Entry
                        </a>

                        <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure you want to delete this blog entry?');" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo (int)$blog["id"]; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">
                            <button type="submit" class="btn btn-danger">
                                Delete Entry
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <?php include 'includes/footer.php'; ?>
    </main>

</body>

</html>