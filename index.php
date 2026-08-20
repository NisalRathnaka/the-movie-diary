<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "includes/db.php";

// Fetch posts joined with users table to get the username
$sql = "SELECT blogpost.*, user.username 
        FROM blogpost 
        LEFT JOIN user ON blogpost.user_id = user.id 
        ORDER BY blogpost.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Movie Diary 🎬 - Home</title>
    <link rel="stylesheet" href="style.css?v=1.1">
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
        <section style="margin-bottom: 30px; text-align: center;">
            <h1 style="font-size: 2.2rem; margin-bottom: 10px;">Welcome to The Movie Diary</h1>
            <p style="color: var(--text-secondary);">Your personal hub for film reviews, TV show analysis, and cinema opinions.</p>
        </section>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="blog-grid">
                <?php while ($blog = mysqli_fetch_assoc($result)): ?>
                    <article class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                        <?php if (!empty($blog['image_url']) && file_exists($blog['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                            <h2 class="card-title">
                                <a href="view.php?id=<?php echo urlencode($blog["id"]); ?>">
                                    <?php echo htmlspecialchars($blog["title"]); ?>
                                </a>
                            </h2>
                            <div class="card-meta">
                                By <strong><?php echo htmlspecialchars($blog["username"] ?? "Anonymous"); ?></strong> • <?php echo date('M d, Y', strtotime($blog["created_at"])); ?>
                            </div>
                            <p class="card-excerpt">
                                <?php echo htmlspecialchars(substr($blog["content"], 0, 120)); ?>...
                            </p>
                            <div style="margin-top: auto; padding-top: 10px;">
                                <a href="view.php?id=<?php echo urlencode($blog["id"]); ?>" style="color: var(--accent-red); font-weight: 600;">
                                    Read Diary Entry →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); margin-top: 40px;">No blogs have been published yet.</p>
        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>   

</body>

</html>