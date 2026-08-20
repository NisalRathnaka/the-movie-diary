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
    $content = trim($_POST["content"]); // CKEditor automatically outputs rich HTML formatting here
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

    <!-- CKEditor 5 CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    <style>
        /* Form Label & Icon Layout */
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--text-primary, #ffffff);
            margin-bottom: 8px;
        }

        .form-group label svg {
            width: 18px;
            height: 18px;
            fill: var(--accent-red, #e50914);
            flex-shrink: 0;
        }

        /* Dark Theme Styling for CKEditor 5 */
        .ck.ck-editor__main > .ck-editor__editable {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
            border-color: #333333 !important;
            min-height: 250px;
            border-bottom-left-radius: var(--radius, 8px) !important;
            border-bottom-right-radius: var(--radius, 8px) !important;
        }

        .ck.ck-editor__main > .ck-editor__editable:focus {
            border-color: var(--accent-red, #e50914) !important;
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.2) !important;
        }

        .ck.ck-toolbar {
            background-color: #1a1a1a !important;
            border-color: #333333 !important;
            border-top-left-radius: var(--radius, 8px) !important;
            border-top-right-radius: var(--radius, 8px) !important;
        }

        .ck.ck-toolbar .ck-button,
        .ck.ck-toolbar .ck-dropdown__button {
            color: #e0e0e0 !important;
        }

        .ck.ck-toolbar .ck-button:hover,
        .ck.ck-toolbar .ck-button.ck-on {
            background-color: #2a2a2a !important;
            color: var(--accent-red, #e50914) !important;
        }

        .ck.ck-toolbar .ck-separator {
            background-color: #333333 !important;
        }

        /* Dropdown Menus (Paragraph Styles) */
        .ck.ck-reset_all, .ck.ck-reset_all * {
            color: #121212;
        }
        
        .ck.ck-list {
            background-color: #1a1a1a !important;
        }

        .ck.ck-list__item .ck-button {
            color: #e0e0e0 !important;
        }

        .ck.ck-list__item .ck-button:hover {
            background-color: #2a2a2a !important;
            color: var(--accent-red, #e50914) !important;
        }
    </style>
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
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="title">
                        <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4h-2l2 4H9L7 4H5L3 8v12h18V4h-3z"/></svg>
                        Movie / Topic Title
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        placeholder="e.g., Oppenheimer (2023) - Ending Explained"
                        required
                    >
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="image">
                        <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        Movie Poster / Cover Image
                    </label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="form-control"
                        accept="image/png, image/jpeg, image/gif, image/webp"
                    >
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="content">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Review / Blog Content
                    </label>
                    <!-- CKEditor replaces this textarea -->
                    <textarea
                        id="content"
                        name="content"
                        class="form-control"
                        placeholder="Write your thoughts, analysis, or review here..."
                    ></textarea>
                </div>

                <div style="display: flex; gap: 15px; align-items: center; margin-top: 25px;">
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

    <!-- Initialize CKEditor -->
    <script>
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'bulletedList', 'numberedList', 'blockQuote', '|',
                    'undo', 'redo'
                ]
            })
            .catch(error => {
                console.error(error);
            });
    </script>

</body>

</html>