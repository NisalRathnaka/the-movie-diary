<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

session_start();
include "includes/db.php";

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = (int)$_SESSION["user_id"];

// Get blogs belonging to the logged-in user
$sql = "SELECT * FROM blogpost
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query Preparation Failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - The Movie Diary 🎬</title>
    <link rel="stylesheet" href="style.css?v=1.1">
</head>

<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">The Movie Diary 🎬</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="create.php" class="btn btn-primary">+ New Post</a></li>
                <li><a href="logout.php" class="btn btn-secondary">Logout</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <section style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 2rem;">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? "User"); ?>!</h1>
                <?php if (isset($_SESSION["email"])): ?>
                    <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                <?php endif; ?>
            </div>
            <a href="create.php" class="btn btn-primary">+ Create New Entry</a>
        </section>

        <h2 style="margin-bottom: 20px; font-size: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">My Diary Entries</h2>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="blog-grid">
                <?php while ($blog = mysqli_fetch_assoc($result)): ?>
                    <article class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <?php if (!empty($blog['image_url']) && file_exists($blog['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                     style="width: 100%; height: 180px; object-fit: cover;">
                            <?php endif; ?>

                            <div style="padding: 20px;">
                                <h3 class="card-title">
                                    <a href="view.php?id=<?php echo urlencode($blog["id"]); ?>">
                                        <?php echo htmlspecialchars($blog["title"]); ?>
                                    </a>
                                </h3>
                                <div class="card-meta">
                                    Created: <?php echo date('M d, Y', strtotime($blog["created_at"])); ?>
                                </div>
                                <p class="card-excerpt">
                                    <?php 
                                        // 1. Strip out HTML tags like <p>, <strong>, <i>
                                        $clean_text = strip_tags($blog["content"]);
                                        
                                        // 2. Decode entities like &amp; back to &
                                        $clean_text = html_entity_decode($clean_text, ENT_QUOTES, 'UTF-8');
                                        
                                        // 3. Truncate to 100 characters
                                        $excerpt = (mb_strlen($clean_text) > 100) ? mb_substr($clean_text, 0, 100) . '...' : $clean_text;
                                        
                                        // 4. Output safely
                                        echo htmlspecialchars($excerpt);
                                    ?>
                                </p>
                            </div>
                        </div>

                        <div style="padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; align-items: center;">
                            <a href="view.php?id=<?php echo urlencode($blog["id"]); ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">View</a>
                            <a href="edit.php?id=<?php echo urlencode($blog["id"]); ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                            
                            <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure you want to delete this entry?');" style="display: inline; margin-left: auto;">
                                <input type="hidden" name="id" value="<?php echo (int)$blog["id"]; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p style="color: var(--text-secondary); margin-bottom: 15px;">You haven't created any movie posts yet.</p>
                <a href="create.php" class="btn btn-primary">+ Write Your First Review</a>
            </div>
        <?php endif; ?>

        <?php mysqli_stmt_close($stmt); ?>

    </main>
    <?php include 'includes/footer.php'; ?>

</body>

</html>