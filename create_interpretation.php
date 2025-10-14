<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

//SECURITY & DATA VALIDATION
requireLogin();
$user_id = $_SESSION['user_id'];

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if ($challenge_id === 0) {
    die("Error: No challenge specified.");
}

// FETCH ORIGINAL CHALLENGE NAME FOR DISPLAY
$challenge_name = "Unknown Challenge";
$sql_challenge = "SELECT challenge_name FROM challenges WHERE challenge_id = ?";
if ($stmt_challenge = $mysqli->prepare($sql_challenge)) {
    $stmt_challenge->bind_param("i", $challenge_id);
    if ($stmt_challenge->execute()) {
        $challenge_name = $stmt_challenge->get_result()->fetch_assoc()['challenge_name'];
    }
    $stmt_challenge->close();
}

//FORM PROCESSING LOGIC
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST['description']);
    $artFilename = null;
    $errorMessage = "";

    //Handle file upload
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
            $errorMessage = "Error: Invalid file type.";
        }
    } else {
        $errorMessage = "Error: Please upload your art file.";
    }

    //Insert into database if no errors
    if (empty($errorMessage)) {
        $sql = "INSERT INTO interpretations (challenge_id, user_id, description, art_filename) VALUES (?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iiss", $challenge_id, $user_id, $description, $artFilename);
            if ($stmt->execute()) {
                //Redirect back to the challenge page to see the new interpretation
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            padding-top: 20px;
        }

        .navbar-custom {
            background-color: var(--primary-bg);
        }

        .form-card {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            color: var(--text-dark);
        }

        .input-style {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            transition: border-color 0.2s;
        }

        .input-style:focus {
            border-color: var(--primary-bg);
            outline: none;
        }

        .btn-submit {
            background-color: var(--primary-bg);
            transition: background-color 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #7b68ee;
        }

        .btn-cancel {
            background-color: #e5e7eb;
            color: var(--text-dark);
            transition: background-color 0.2s ease;
        }

        .btn-cancel:hover {
            background-color: #d1d5db;
        }

        .drop-zone {
            border: 3px dashed var(--light-purple);
            background-color: #f7f7ff;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .drop-zone.drag-over {
            border-color: var(--primary-bg);
            background-color: #e6e0fc;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 20px;
            z-index: 10;
        }
    </style>
</head>

<body class="bg-gradient-to-b from-blue-300 to-purple-400 min-h-screen font-sans">
    <?php require 'partials/navbar.php'; ?>
    <div class="container mx-auto p-4 md:p-8 my-8">
        <h1 class="text-3xl font-bold text-white mb-2 text-center">Submit Your Interpretation</h1>
        <p class="text-white/80 mb-6 text-center">For the challenge: <strong class="font-semibold"><?php echo htmlspecialchars($challenge_name); ?></strong></p>

        <div id="mainFormContainer" class="form-card max-w-3xl mx-auto p-6 md:p-10 relative">
            <?php if (!empty($errorMessage)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>

            <form id="interpretationForm" action="create_interpretation.php?challenge_id=<?php echo $challenge_id; ?>" method="post" enctype="multipart/form-data">
                <div class="mb-6">
                    <label for="artFile" class="block text-lg font-semibold mb-2">Upload Your Art</label>
                    <div id="dropZone" class="drop-zone p-8 text-center rounded-xl cursor-pointer flex flex-col items-center justify-center">
                        <i class="fas fa-upload text-4xl text-gray-500 mb-3"></i>
                        <p class="text-gray-700 font-medium">Drag & Drop your image here, or click to select.</p>
                        <input type="file" id="artFile" name="artFile" accept="image/*" class="hidden" required onchange="handleFileSelect(this)">
                    </div>
                    <div id="filePreviewContainer" class="mt-4 hidden ...">
                        <img id="imagePreview" class="... " alt="Art Preview">
                    </div>
                </div>
                <div class="mb-8">
                    <label for="description" class="block text-lg font-semibold mb-2">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe your interpretation..." class="input-style w-full resize-none"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='challengepage.php?id=<?php echo $challenge_id; ?>'" class="btn-cancel py-2 px-6 rounded-full font-bold shadow-md">
                        <i class="fas fa-times-circle mr-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn-submit text-white py-2 px-6 rounded-full font-bold shadow-md">
                        <i class="fas fa-paper-plane mr-2"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Run the setup functions once the page has loaded
        window.onload = () => {
            setupDragDrop();
        };

        /**
         * Sets up the event listeners for the drag-and-drop file upload area.
         */
        function setupDragDrop() {
            const dropZone = document.getElementById('dropZone');
            const artFile = document.getElementById('artFile');

            // Make the entire drop zone clickable to open the file selector
            dropZone.addEventListener('click', () => artFile.click());

            // Prevent default browser behavior for drag events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            // Add a visual indicator when an item is dragged over the drop zone
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
            });

            // Remove the visual indicator when the item leaves the drop zone
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
            });

            // Handle the file drop
            dropZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    artFile.files = files; // Assign the dropped files to our hidden file input
                    handleFileSelect(artFile); // Trigger the preview function
                }
            }
        }

        /**
         * Handles the file preview when a file is selected either by clicking or dropping.
         * @param {HTMLInputElement} input - The file input element.
         */
        function handleFileSelect(input) {
            const file = input.files[0];
            const previewContainer = document.getElementById('filePreviewContainer');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const imagePreview = document.getElementById('imagePreview');

            if (file) {
                // Use FileReader to get a preview of the image
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);

                fileNameDisplay.textContent = `Selected file: ${file.name}`;
                document.getElementById('dropZone').classList.add('hidden'); // Hide the drop zone
            } else {
                // If no file is selected (or selection is canceled), reset the view
                previewContainer.classList.add('hidden');
                document.getElementById('dropZone').classList.remove('hidden'); // Show the drop zone again
            }
        }

        /**
         * Handles the cancel button click.
         * This function is already in your button's onclick attribute.
         */
        function handleCancel() {
            // Gets the challenge_id from the URL to return to the correct page
            const params = new URLSearchParams(window.location.search);
            const challengeId = params.get('challenge_id');
            if (challengeId) {
                window.location.href = `challengepage.php?id=${challengeId}`;
            } else {
                window.location.href = 'listofarts.php'; // Fallback redirect
            }
        }
    </script>
</body>

</html>