<?php
session_start();
include "includes/db.php";

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Make sure blog ID exists
if (!isset($_GET["id"]) || !filter_var($_GET["id"], FILTER_VALIDATE_INT)) {
    header("Location: dashboard.php");
    exit();
}

$blog_id = (int)$_GET["id"];
$user_id = $_SESSION["user_id"];
$message = "";

// Get the blog and make sure it belongs to the logged-in user
$sql = "SELECT * FROM blogpost WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $blog_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if blog exists and belongs to user
if (mysqli_num_rows($result) !== 1) {
    http_response_code(403);
    echo "You are not authorized to edit this blog or it does not exist.";
    mysqli_stmt_close($stmt);
    exit();
}

$blog = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Update blog when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $image_path = $blog['image_url']; // Keep existing image path by default

    // Handle new image upload if provided
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

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $new_filename = uniqid('img_', true) . '.' . $extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $target_file)) {
                // Delete old local image file from server if it exists
                if (!empty($blog['image_url']) && file_exists($blog['image_url'])) {
                    unlink($blog['image_url']);
                }
                $image_path = $target_file;
            } else {
                $message = "Failed to upload new image.";
            }
        } else {
            $message = "Invalid file type or file exceeds 5MB limit.";
        }
    }

    if (empty($message)) {
        if (empty($title) || empty($content)) {
            $message = "Title and content cannot be empty.";
        } else {
            $sql = "UPDATE blogpost 
                    SET title = ?, content = ?, image_url = ?
                    WHERE id = ? AND user_id = ?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "sssii",
                $title,
                $content,
                $image_path,
                $blog_id,
                $user_id
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: view.php?id=" . urlencode($blog_id));
                exit();
            } else {
                $message = "Failed to update blog.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry - The Movie Diary 🎬</title>
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
            <h1 style="font-size: 1.8rem; margin-bottom: 20px;">Edit Diary Entry</h1>

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
                        value="<?php echo htmlspecialchars($blog["title"]); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="image">Movie Poster / Cover Image (Upload New Image to Replace)</label>
                    <?php if (!empty($blog['image_url'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" alt="Current Image" style="max-height: 120px; border-radius: 6px; display: block; margin-bottom: 5px;">
                            <small style="color: var(--text-secondary);">Current Image File</small>
                        </div>
                    <?php endif; ?>
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
                        required
                    ><?php echo htmlspecialchars($blog["content"]); ?></textarea>
                </div>

                <div style="display: flex; gap: 15px; align-items: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        Update Entry
                    </button>
                    <a href="view.php?id=<?php echo urlencode($blog_id); ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        
    </main>
    <?php include 'includes/footer.php'; ?>

</body>

</html>