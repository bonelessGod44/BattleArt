<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$user_id = $_SESSION['user_id'];
$challenge_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($challenge_id === 0) die("Invalid challenge ID.");

// --- FORM SUBMISSION LOGIC ---
$errorMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $challengeName = trim($_POST['challengeName']);
    $description = trim($_POST['challengeDescription']);
    $category = trim($_POST['category']);
    $newArtFilename = null;

    // Fetch the old filename before updating, in case we need to delete it.
    $old_filename_sql = "SELECT original_art_filename FROM challenges WHERE challenge_id = ? AND user_id = ?";
    if ($stmt_old = $mysqli->prepare($old_filename_sql)) {
        $stmt_old->bind_param("ii", $challenge_id, $user_id);
        $stmt_old->execute();
        $old_challenge = $stmt_old->get_result()->fetch_assoc();
        $stmt_old->close();
    }

    // Handle optional new file upload
    if (isset($_FILES['artFile']) && $_FILES['artFile']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['artFile']['tmp_name'];
        $file_name = $_FILES['artFile']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $newArtFilename = uniqid('art_', true) . '.' . $file_extension;
            if (move_uploaded_file($file_tmp_path, 'assets/uploads/' . $newArtFilename)) {
                // If a new file is successfully uploaded, delete the old one
                if ($old_challenge && !empty($old_challenge['original_art_filename']) && file_exists('assets/uploads/' . $old_challenge['original_art_filename'])) {
                    unlink('assets/uploads/' . $old_challenge['original_art_filename']);
                }
            } else {
                $newArtFilename = null;
                $errorMessage = "Error: Could not save the new image.";
            }
        } else {
            $errorMessage = "Error: Invalid file type.";
        }
    }

    // Build the UPDATE query only if there were no upload errors
    if (empty($errorMessage)) {
        $sql_update = "UPDATE challenges SET challenge_name = ?, challenge_description = ?, category = ?";
        $params = [$challengeName, $description, $category];
        $types = "sss";
        if ($newArtFilename) {
            $sql_update .= ", original_art_filename = ?";
            $params[] = $newArtFilename;
            $types .= "s";
        }
        $sql_update .= " WHERE challenge_id = ? AND user_id = ?";
        $params[] = $challenge_id;
        $params[] = $user_id;
        $types .= "ii";

        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param($types, ...$params);
            if ($stmt_update->execute()) {
                header("Location: challengepage.php?id=" . $challenge_id);
                exit;
            } else {
                $errorMessage = "Error updating challenge.";
            }
            $stmt_update->close();
        }
    }
}
$challenge = null;
$sql_fetch = "SELECT * FROM challenges WHERE challenge_id = ? AND user_id = ?";
if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("ii", $challenge_id, $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows === 1) {
        $challenge = $result->fetch_assoc();
    } else {
        die("You do not have permission to edit this challenge.");
    }
    $stmt_fetch->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Challenge</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
            --text-dark: #333;
        }

        body {
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-top: 20px;
        }

        .form-card {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            color: var(--text-dark);
        }

        .drop-zone {
            border: 3px dashed var(--light-purple);
            background-color: #f7f7ff;
            transition: all 0.2s;
        }

        .drop-zone.drag-over {
            border-color: var(--primary-bg);
            background-color: #e6e0fc;
        }
    </style>
</head>

<body class="font-sans">
    <?php require 'partials/navbar.php'; ?>

    <div class="container my-5">
        <div class="form-card col-lg-8 mx-auto p-4 p-md-5">
            <h1 class="h3 fw-bold text-dark mb-4 text-center">Edit Your Challenge</h1>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            <form action="edit_challenge.php?id=<?php echo $challenge_id; ?>" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="challengeName" class="form-label fw-semibold">Challenge Name</label>
                    <input type="text" name="challengeName" class="form-control" value="<?php echo htmlspecialchars($challenge['challenge_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label fw-semibold">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="digital_painting" <?php if ($challenge['category'] == 'digital_painting') echo 'selected'; ?>>Digital Painting</option>
                        <option value="sci-fi" <?php if ($challenge['category'] == 'sci-fi') echo 'selected'; ?>>Sci-Fi</option>
                        <option value="fantasy" <?php if ($challenge['category'] == 'fantasy') echo 'selected'; ?>>Fantasy</option>
                        <option value="abstract" <?php if ($challenge['category'] == 'abstract') echo 'selected'; ?>>Abstract</option>
                        <option value="portraits" <?php if ($challenge['category'] == 'portraits') echo 'selected'; ?>>Portraits</option>
                        <option value="landscapes" <?php if ($challenge['category'] == 'landscapes') echo 'selected'; ?>>Landscapes</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Update Original Art (Optional)</label>
                    <div class="mb-3 text-center">
                        <p class="small text-muted mb-2">Current Image:</p>
                        <img src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" class="img-thumbnail" style="max-width: 200px;">
                    </div>
                    <div id="dropZone" class="drop-zone p-4 text-center rounded-3 cursor-pointer">
                        <i class="fas fa-upload fs-2 text-muted mb-2"></i>
                        <p class="text-muted small">Drag & Drop to replace, or click to select a new file.</p>
                        <input type="file" id="artFile" name="artFile" accept="image/*" class="d-none" onchange="handleFileSelect(this)">
                    </div>
                    <div id="filePreviewContainer" class="mt-3 d-none text-center">
                        <p id="fileNameDisplay" class="small text-muted mb-2"></p>
                        <img id="imagePreview" class="img-thumbnail" style="max-height: 200px;" alt="New Art Preview">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="challengeDescription" class="form-label fw-semibold">Description</label>
                    <textarea name="challengeDescription" rows="4" class="form-control" required><?php echo htmlspecialchars($challenge['challenge_description']); ?></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="challengepage.php?id=<?php echo $challenge_id; ?>" class="btn btn-secondary rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = () => {
            setupDragDrop();
        };

        function setupDragDrop() {
            const dropZone = document.getElementById('dropZone');
            const artFile = document.getElementById('artFile');
            if (!dropZone || !artFile) return;

            dropZone.addEventListener('click', () => artFile.click());
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
                document.body.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
            });
            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    artFile.files = files;
                    handleFileSelect(artFile);
                }
            }, false);
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            const previewContainer = document.getElementById('filePreviewContainer');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const imagePreview = document.getElementById('imagePreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('d-none'); // Use Bootstrap's d-none
                };
                reader.readAsDataURL(file);
                fileNameDisplay.textContent = `New file selected: ${file.name}`;
                document.getElementById('dropZone').classList.add('d-none'); // Use Bootstrap's d-none
            } else {
                previewContainer.classList.add('d-none');
                document.getElementById('dropZone').classList.remove('d-none');
            }
        }
    </script>
</body>

</html>