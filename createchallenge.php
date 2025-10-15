<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

//SECURITY: Ensure only logged-in users can access this page
requireLogin();
$user_id = $_SESSION['user_id'];

//FORM PROCESSING LOGIC
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize form data
    $challengeName = trim($_POST['challengeName']);
    $challengeDescription = trim($_POST['challengeDescription']);
    $category = trim($_POST['category']);

    $artFilename = null;
    $errorMessage = "";

    //Handle the file upload
    if (isset($_FILES['artFile']) && $_FILES['artFile']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['artFile']['tmp_name'];
        $file_name = $_FILES['artFile']['name'];
        $file_size = $_FILES['artFile']['size'];

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions) && $file_size <= $max_file_size) {
            //Generate a unique filename to prevent overwrites
            $artFilename = uniqid('art_', true) . '.' . $file_extension;
            $dest_path = 'assets/uploads/' . $artFilename; // Ensure 'assets/uploads/' folder exists and is writable

            if (!move_uploaded_file($file_tmp_path, $dest_path)) {
                $errorMessage = "Error: Failed to move the uploaded file.";
            }
        } else {
            $errorMessage = "Error: Invalid file type or size. Max 5MB for JPG, PNG, GIF.";
        }
    } else {
        $errorMessage = "Error: No file was uploaded or an error occurred during upload.";
    }

    //If there were no errors, insert into the database
    if (empty($errorMessage)) {
        $sql = "INSERT INTO challenges (user_id, challenge_name, challenge_description, category, original_art_filename) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("issss", $user_id, $challengeName, $challengeDescription, $category, $artFilename);

            if ($stmt->execute()) {
                //Success: Redirect to a gallery or the new art details page
                //We'll store a success message in the session to display after redirect
                $_SESSION['message'] = "Challenge created successfully!";
                header("Location: listofarts.php"); // Redirect to your main art list
                exit;
            } else {
                $errorMessage = "Error: Could not save the challenge to the database.";
            }
            $stmt->close();
        }
    }

    //If we are here, an error occurred. Store it in the session to display.
    $_SESSION['error_message'] = $errorMessage;
    header("Location: createchallenge.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Challenge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding-top: 30px;
        }

        /* Custom styles for Bootstrap components */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            color: var(--text-dark);
        }

        .form-control,
        .form-select {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            transition: border-color 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-bg);
            box-shadow: 0 0 0 0.25rem rgba(140, 118, 236, 0.25);
            outline: none;
        }

        .btn-submit {
            background-color: var(--primary-bg);
            border-color: var(--primary-bg);
            color: white;
            transition: background-color 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #7b68ee;
            border-color: #7b68ee;
            color: white;
        }

        .btn-cancel {
            background-color: #e5e7eb;
            color: var(--text-dark);
            border-color: #e5e7eb;
            transition: background-color 0.2s ease;
        }

        .btn-cancel:hover {
            background-color: #d1d5db;
            border-color: #d1d5db;
        }

        .drop-zone {
            border: 3px dashed var(--light-purple);
            background-color: #f7f7ff;
            transition: background-color 0.2s, border-color 0.2s;
            cursor: pointer;
        }

        .drop-zone.drag-over {
            border-color: var(--primary-bg);
            background-color: #e6e0fc;
        }

        textarea.form-control {
            resize: none;
        }
    </style>
</head>

<body class="min-vh-100">
    <?php include 'partials/navbar.php'; ?>
    <div class="container my-5">
        <h1 class="fw-bold text-white mb-5 text-center">Start a New Creative Challenge</h1>

        <div class="card shadow-lg mx-auto p-4 p-md-5" style="max-width: 50rem;">

            <?php
            if (isset($_SESSION['error_message'])) {
                echo "<div class='alert alert-danger mb-4' role='alert'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
                unset($_SESSION['error_message']);
            }
            ?>

            <form id="challengeForm" action="createchallenge.php" method="post" enctype="multipart/form-data">

                <div class="mb-4">
                    <label for="challengeName" class="form-label fs-5 fw-semibold">Challenge Name</label>
                    <input type="text" id="challengeName" name="challengeName"
                        placeholder="E.g., Cyberpunk Samurai, Magical Artifact, etc." class="form-control form-control-lg"
                        required>
                </div>

                <div class="mb-4">
                    <label for="category" class="form-label fs-5 fw-semibold">Category</label>
                    <select id="category" name="category" class="form-select form-select-lg" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="digital_painting">Digital Painting</option>
                        <option value="sci-fi">Sci-Fi</option>
                        <option value="fantasy">Fantasy</option>
                        <option value="abstract">Abstract</option>
                        <option value="portraits">Portraits</option>
                        <option value="landscapes">Landscapes</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="artFile" class="form-label fs-5 fw-semibold">Upload Original Art</label>
                    <div id="dropZone"
                        class="drop-zone p-5 text-center rounded-3 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-upload fs-1 text-secondary mb-3"></i>
                        <p class="text-body-secondary fw-medium">Drag & Drop your image here, or click to select file.</p>
                        <input type="file" id="artFile" name="artFile" accept="image/*" class="d-none" required
                            onchange="handleFileSelect(this)">
                    </div>
                    <div id="filePreviewContainer" class="mt-4 d-none border rounded-3 p-2 bg-light">
                        <p id="fileNameDisplay" class="small fw-medium text-body-secondary mb-2"></p>
                        <img id="imagePreview" class="img-fluid rounded-3 shadow-sm mx-auto d-block"
                            style="max-height: 256px;" alt="Art Preview">
                    </div>
                </div>

                <div class="mb-5">
                    <label for="challengeDescription" class="form-label fs-5 fw-semibold">Description</label>
                    <textarea id="challengeDescription" name="challengeDescription" rows="4"
                        placeholder="Describe your artwork and provide guidelines or inspiration..."
                        class="form-control form-control-lg" required></textarea>
                </div>

                <div class="d-flex justify-content-end gap-3">
                    <button type="button" onclick="handleCancel()"
                        class="btn btn-cancel rounded-pill py-2 px-4 fw-bold shadow-sm">
                        <i class="fas fa-times-circle me-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-submit rounded-pill py-2 px-4 fw-bold shadow-sm">
                        <i class="fas fa-paper-plane me-2"></i> Submit Challenge
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = () => {
            setupDragDrop();
        };

        function setupDragDrop() {
            const dropZone = document.getElementById('dropZone');
            const artFile = document.getElementById('artFile');

            dropZone.addEventListener('click', () => artFile.click());

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
            });

            dropZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    artFile.files = files;
                    handleFileSelect(artFile);
                }
            }
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            const previewContainer = document.getElementById('filePreviewContainer');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const imagePreview = document.getElementById('imagePreview');
            const dropZone = document.getElementById('dropZone');

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
                fileNameDisplay.textContent = `Selected file: ${file.name}`;
                dropZone.classList.add('d-none');
            } else {
                previewContainer.classList.add('d-none');
                dropZone.classList.remove('d-none');
            }
        }

        function handleCancel() {
            window.location.href = 'listofarts.php';
        }
    </script>
</body>

</html>