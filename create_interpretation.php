<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

// 1. SECURITY & DATA VALIDATION
requireLogin();
$user_id = $_SESSION['user_id'];

// Get the ID of the challenge we are interpreting from the URL
$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if ($challenge_id === 0) {
    die("Error: No challenge specified.");
}

// 2. FETCH ORIGINAL CHALLENGE NAME FOR DISPLAY
$challenge_name = "Unknown Challenge";
$sql_challenge = "SELECT challenge_name FROM challenges WHERE challenge_id = ?";
if ($stmt_challenge = $mysqli->prepare($sql_challenge)) {
    $stmt_challenge->bind_param("i", $challenge_id);
    if ($stmt_challenge->execute()) {
        $result = $stmt_challenge->get_result();
        if ($result->num_rows > 0) {
            $challenge_name = $result->fetch_assoc()['challenge_name'];
        }
    }
    $stmt_challenge->close();
}

// 3. FORM PROCESSING LOGIC
$errorMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST['description']);
    $artFilename = null;

    // Handle file upload
    if (isset($_FILES['artFile']) && $_FILES['artFile']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['artFile']['tmp_name'];
        $file_name = $_FILES['artFile']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $artFilename = uniqid('interp_', true) . '.' . $file_extension;
            $dest_path = 'assets/uploads/' . $artFilename;
            if (!move_uploaded_file($file_tmp_path, $dest_path)) {
                $errorMessage = "Error: Failed to save the uploaded file.";
            }
        } else {
            $errorMessage = "Error: Invalid file type (only JPG, PNG, GIF allowed).";
        }
    } else {
        $errorMessage = "Error: Please upload your art file.";
    }

    // Insert into database if no errors
    if (empty($errorMessage)) {
        $sql = "INSERT INTO interpretations (challenge_id, user_id, description, art_filename) VALUES (?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iiss", $challenge_id, $user_id, $description, $artFilename);
            if ($stmt->execute()) {
                // Also create a notification for the original author
                $owner_id = null;
                $owner_sql = "SELECT user_id FROM challenges WHERE challenge_id = ?";
                if ($owner_stmt = $mysqli->prepare($owner_sql)) {
                    $owner_stmt->bind_param("i", $challenge_id);
                    $owner_stmt->execute();
                    if ($row = $owner_stmt->get_result()->fetch_assoc()) {
                        $owner_id = $row['user_id'];
                    }
                    $owner_stmt->close();
                }
                if ($owner_id && $owner_id != $user_id) {
                    $notify_sql = "INSERT INTO notifications (recipient_user_id, sender_user_id, type, target_id) VALUES (?, ?, 'interpretation', ?)";
                    if ($notify_stmt = $mysqli->prepare($notify_sql)) {
                        $notify_stmt->bind_param("iii", $owner_id, $user_id, $challenge_id);
                        $notify_stmt->execute();
                        $notify_stmt->close();
                    }
                }
                // Redirect back to the challenge page
                header("Location: challengepage.php?id=" . $challenge_id);
                exit;
            } else {
                $errorMessage = "Database error: Could not save interpretation.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Interpretation</title>
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
            <div class="text-center mb-4">
                <h1 class="h3 fw-bold text-dark">Submit Your Interpretation</h1>
                <p class="text-muted">For the challenge: <strong style="color: var(--primary-bg);"><?php echo htmlspecialchars($challenge_name); ?></strong></p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form id="interpretationForm" action="create_interpretation.php?challenge_id=<?php echo $challenge_id; ?>" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="artFile" class="form-label fw-semibold">Upload Your Art</label>
                    <div id="dropZone" class="drop-zone p-5 text-center rounded-3 d-flex flex-column align-items-center justify-content-center" style="cursor: pointer;">
                        <i class="fas fa-upload fs-2 text-muted mb-2"></i>
                        <p class="text-muted small">Drag & Drop your image here, or click to select.</p>
                        <input type="file" id="artFile" name="artFile" accept="image/*" class="d-none" required onchange="handleFileSelect(this)">
                    </div>
                    <div id="filePreviewContainer" class="mt-3 d-none text-center">
                        <p id="fileNameDisplay" class="small text-muted mb-2"></p>
                        <img id="imagePreview" class="img-thumbnail" style="max-height: 200px;" alt="Art Preview">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe your creative choices..." class="form-control"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="challengepage.php?id=<?php echo $challenge_id; ?>" class="btn btn-light rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Submit</button>
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
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
                fileNameDisplay.textContent = `New file selected: ${file.name}`;
                document.getElementById('dropZone').classList.add('d-none');
            } else {
                previewContainer.classList.add('d-none');
                document.getElementById('dropZone').classList.remove('d-none');
            }
        }
    </script>
</body>

</html>