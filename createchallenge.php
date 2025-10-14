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
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>

<body class="min-h-screen font-sans">
    <?php include 'partials/navbar.php'; ?>
    <div class="container mx-auto p-4 md:p-8 my-8">
        <h1 class="text-3xl font-bold text-white mb-6 text-center">Start a New Creative Challenge</h1>

        <div class="form-card max-w-3xl mx-auto p-6 md:p-10 relative">

            <?php
            if (isset($_SESSION['error_message'])) {
                echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6' role='alert'>" . $_SESSION['error_message'] . "</div>";
                unset($_SESSION['error_message']);
            }
            ?>

            <form id="challengeForm" action="createchallenge.php" method="post" enctype="multipart/form-data">

                <div class="mb-6">
                    <label for="challengeName" class="block text-lg font-semibold mb-2">Challenge Name</label>
                    <input type="text" id="challengeName" name="challengeName"
                        placeholder="E.g., Cyberpunk Samurai, Magical Artifact, etc." class="input-style w-full"
                        required>
                </div>

                <div class="mb-6">
                    <label for="category" class="block text-lg font-semibold mb-2">Category</label>
                    <select id="category" name="category" class="input-style w-full" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="digital_painting">Digital Painting</option>
                        <option value="sci-fi">Sci-Fi</option>
                        <option value="fantasy">Fantasy</option>
                        <option value="abstract">Abstract</option>
                        <option value="portraits">Portraits</option>
                        <option value="landscapes">Landscapes</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="artFile" class="block text-lg font-semibold mb-2">Upload Original Art</label>
                    <div id="dropZone"
                        class="drop-zone p-8 text-center rounded-xl cursor-pointer flex flex-col items-center justify-center">
                        <i class="fas fa-upload text-4xl text-gray-500 mb-3"></i>
                        <p class="text-gray-700 font-medium">Drag & Drop your image here, or click to select file.</p>
                        <input type="file" id="artFile" name="artFile" accept="image/*" class="hidden" required
                            onchange="handleFileSelect(this)">
                    </div>
                    <div id="filePreviewContainer" class="mt-4 hidden border rounded-lg p-2 bg-gray-50">
                        <p id="fileNameDisplay" class="text-sm font-medium text-gray-700 mb-2"></p>
                        <img id="imagePreview" class="w-full max-h-64 object-contain rounded-lg shadow-md mx-auto"
                            alt="Art Preview">
                    </div>
                </div>

                <div class="mb-8">
                    <label for="challengeDescription" class="block text-lg font-semibold mb-2">Description</label>
                    <textarea id="challengeDescription" name="challengeDescription" rows="4"
                        placeholder="Describe your artwork and provide guidelines or inspiration..."
                        class="input-style w-full resize-none" required></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="handleCancel()"
                        class="btn-cancel py-2 px-6 rounded-full font-bold shadow-md">
                        <i class="fas fa-times-circle mr-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn-submit text-white py-2 px-6 rounded-full font-bold shadow-md">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Challenge
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        window.onload = () => {
            setupDragDrop();
        };

        function handleChallengeSubmit(event) {
            event.preventDefault();

            const challengeName = document.getElementById('challengeName').value;
            const file = document.getElementById('artFile').files[0];

            console.log("--- Form Submission Attempted ---");
            console.log("Challenge Name:", challengeName);
            console.log("File Name:", file ? file.name : 'No file selected');
            console.log("Action: Data would be sent to a server-side script (e.g., PHP) for processing and saving.");
            console.log("---------------------------------");

            showSubmissionResult(true, `Challenge "${challengeName}" submitted successfully (client-side simulation). A PHP backend would handle the persistence.`);
        }

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

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
                fileNameDisplay.textContent = `Selected file: ${file.name}`;
                document.getElementById('dropZone').classList.add('hidden');
            } else {
                previewContainer.classList.add('hidden');
                document.getElementById('dropZone').classList.remove('hidden');
            }
        }

        function handleCancel() {
            window.location.href = 'listofarts.php';
        }

        // Displays a custom result message instead of an alert
        function showSubmissionResult(isSuccess, message) {
            const formContainer = document.getElementById('mainFormContainer');
            const iconClass = isSuccess ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-red-500';
            const buttonText = isSuccess ? 'Return to Form' : 'Try Again';
            const buttonAction = isSuccess ? 'window.location.reload()' : 'window.location.reload()'; // Reload to reset the form

            formContainer.innerHTML = `
                <div class="text-center p-8">
                    <i class="${iconClass} text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">${isSuccess ? 'Submission Simulated' : 'Submission Failed'}</h2>
                    <p class="text-gray-600 mb-6">${message}</p>
                    <button onclick="${buttonAction}" class="btn-submit text-white py-2 px-6 rounded-full font-bold shadow-md">
                        ${buttonText}
                    </button>
                </div>
            `;
        }
    </script>
</body>

</html>