<?php
session_start();
include "includes/db.php";

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $user_id = $_SESSION["user_id"];
    $image_path = NULL;

    // Handle File Upload
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $allowed_mime_types = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        $file_tmp  = $_FILES["image"]["tmp_name"];
        $file_size = $_FILES["image"]["size"];

        // Verify true MIME type using Fileinfo
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        if (array_key_exists($mime_type, $allowed_mime_types) && $file_size <= 5 * 1024 * 1024) {
            $extension = $allowed_mime_types[$mime_type];
            $target_dir = "uploads/";

            // Ensure upload directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $new_filename = uniqid('img_', true) . '.' . $extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_path = $target_file;
            } else {
                $message = "Failed to upload image to the server.";
            }
        } else {
            $message = "Invalid file format or file size exceeds 5MB limit.";
        }
    }

    // Insert blog post if validation passed
    if (empty($message)) {
        if (empty($title) || empty($content)) {
            $message = "Title and content cannot be empty.";
        } else {
            $sql = "INSERT INTO blogpost (user_id, title, content, image_url) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $content, $image_path);

                if (mysqli_stmt_execute($stmt)) {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "Failed to create entry. Please try again.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Entry - The Movie Diary 🎬</title>
    <link rel="stylesheet" href="style.css?v=1.1">
</head>

<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">The Movie Diary 🎬</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create.php" class="btn btn-primary">+ New Post</a></li>
                <li><a href="logout.php" class="btn btn-secondary">Logout</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <div class="card" style="max-width: 800px; margin: 20px auto; padding: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 20px;">Create New Diary Entry</h1>

            <?php if (!empty($message)): ?>
                <p style="color: var(--accent-red); margin-bottom: 15px; font-weight: 600;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Movie/Topic Title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        placeholder="e.g., Oppenheimer (2023) - Ending Explained"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="image">Movie Poster / Cover Image (Upload from Device)</label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="form-control"
                        accept="image/png, image/jpeg, image/gif, image/webp"
                    >
                </div>

                <div class="form-group">
                    <label for="content">Review / Blog Content</label>
                    <textarea
                        id="content"
                        name="content"
                        class="form-control"
                        rows="12"
                        placeholder="Write your thoughts, analysis, or review here..."
                        required
                    ></textarea>
                </div>

                <div style="display: flex; gap: 15px; align-items: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        Publish Entry
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
       
    </main>
     <?php include 'includes/footer.php'; ?>

</body>

</html>