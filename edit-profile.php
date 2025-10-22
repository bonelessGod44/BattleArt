<?php
session_start();
require_once "config.php"; // Your database connection file
date_default_timezone_set('Asia/Manila'); // Set a consistent timezone

//AUTHENTICATION & SECURITY: Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Determine the correct profile link based on user type
$profile_link = 'profile.php'; // Default for regular users
if (isset($_SESSION['user_type']) && strtolower($_SESSION['user_type']) === 'admin') {
    $profile_link = 'admin_profile.php'; // Link for admins
}
$message = "";

//FORM SUBMISSION LOGIC: Runs when the "Save Changes" button is clicked
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and retrieve text inputs
    $fullName = trim($_POST['fullName']);
    $userBio = trim($_POST['userBio']);

    // Server-side validation for bio word limit
    $char_limit = 500;
    if (mb_strlen($userBio) > $char_limit) {
        // If over the limit, truncate the string to 500 characters
        $userBio = mb_substr($userBio, 0, $char_limit);
    }

    // Retrieve toggle values
    $showArt = isset($_POST['toggleArt']) ? 1 : 0;
    $showHistory = isset($_POST['toggleHistory']) ? 1 : 0;
    $showComments = isset($_POST['toggleComments']) ? 1 : 0;

    $profilePicFilename = null;
    $bannerPicFilename = null;

    // Handle CROPPED profile picture upload from Base64 string
    if (isset($_POST['croppedImage']) && !empty($_POST['croppedImage'])) {
        $dataURL = $_POST['croppedImage'];
        list($type, $data) = explode(';', $dataURL);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        if ($data) {
            $profilePicFilename = uniqid('profile_', true) . '.png';
            $dest_path = 'assets/uploads/' . $profilePicFilename;
            file_put_contents($dest_path, $data);
        }
    }

    // Handle CROPPED banner image upload from Base64 string
    if (isset($_POST['croppedBanner']) && !empty($_POST['croppedBanner'])) {
        $dataURL = $_POST['croppedBanner'];
        list($type, $data) = explode(';', $dataURL);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        if ($data) {
            $bannerPicFilename = uniqid('banner_', true) . '.png';
            $dest_path = 'assets/uploads/' . $bannerPicFilename;
            file_put_contents($dest_path, $data);
        }
    }

    // Dynamically build the SQL query based on what was submitted
    $sql = "UPDATE users SET user_userName = ?, user_bio = ?, show_art = ?, show_history = ?, show_comments = ?";
    $params = [$fullName, $userBio, $showArt, $showHistory, $showComments];
    $types = "ssiii";

    if ($profilePicFilename) {
        $sql .= ", user_profile_pic = ?";
        $params[] = $profilePicFilename;
        $types .= "s";
    }
    if ($bannerPicFilename) {
        $sql .= ", user_banner_pic = ?";
        $params[] = $bannerPicFilename;
        $types .= "s";
    }

    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['message'] = "<div class='p-3 mb-4 rounded-lg text-sm font-medium bg-green-100 text-green-800'>Profile updated successfully!</div>";
    } else {
        $_SESSION['message'] = "<div class='p-3 mb-4 rounded-lg text-sm font-medium bg-red-100 text-red-800'>Error: Could not update profile.</div>";
    }
    $stmt->close();
    if (isset($_SESSION['user_type']) && strtolower($_SESSION['user_type']) === 'admin') {
        // Redirect Admin users to their specific profile page
        header("Location: admin_profile.php");
    } else {
        // Redirect regular users to the standard profile page
        header("Location: profile.php");
    }
    exit;
}

//DATA FETCHING: Get current user data to display in the form
$sql = "SELECT user_userName, user_email, user_profile_pic, user_banner_pic, user_bio, show_art, show_history, show_comments FROM users WHERE user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($userName, $userEmail, $userProfilePic, $userBannerPic, $userBio, $showArt, $showHistory, $showComments);
    $stmt->fetch();
    $stmt->close();
}
$profilePicPath = !empty($userProfilePic) ? 'assets/uploads/' . $userProfilePic : 'assets/images/blank-profile-picture.png'; // Use a default image if none is set
$bannerPicPath = !empty($userBannerPic) ? 'assets/uploads/' . $userBannerPic : 'assets/images/night-road.png'; // Fallback to a default banner

// Check for a feedback message from a previous (failed) submission on this page
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #a78bfa 0%, #d8b4fe 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem 1rem;
        }

        .main-container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .banner {
            height: 180px;
            background-image: url('<?php echo $bannerPicPath; ?>');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.875rem;
            font-weight: 800;
            padding: 1rem;
        }

        .profile-img-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #333;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            flex-shrink: 0;
        }

        .toggle-label input:checked+.slider {
            background-color: #8b5cf6;
        }

        .slider {
            background-color: #e0e7ff;
            transition: .4s;
        }

        .toggle-label .slider+span {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <a href="<?php echo $profile_link; ?>" class="absolute top-4 left-4 bg-white bg-opacity-30 backdrop-blur-sm text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-md hover:bg-opacity-40 transition duration-200 flex items-center z-10">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Profile
    </a>

    <div id="cropperModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-xl font-bold text-gray-800">Reposition and Crop Image</h3>
            <div class="w-full h-64 bg-gray-100"><img id="imageToCrop" src=""></div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">Cancel</button>
                <button type="button" id="cropButton" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition">Crop & Apply</button>
            </div>
        </div>
    </div>

    <div id="bannerCropperModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 space-y-4">
            <h3 class="text-xl font-bold text-gray-800">Reposition and Crop Banner</h3>
            <div class="w-full h-80 bg-gray-100"><img id="bannerToCrop" src=""></div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeBannerModal()" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">Cancel</button>
                <button type="button" id="cropBannerButton" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition">Crop & Apply Banner</button>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="banner">
            <label for="bannerUpload" class="absolute top-4 right-4 bg-white bg-opacity-30 backdrop-blur-sm text-white text-sm font-semibold py-2 px-3 rounded-xl shadow-md hover:bg-opacity-40 transition duration-200 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.218A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.218A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Change Banner
            </label>
            <input type="file" id="bannerUpload" class="hidden" accept="image/*">
        </div>

        <form id="profileSettingsForm" action="edit-profile.php" method="post" enctype="multipart/form-data" class="px-8 pt-6 pb-8 space-y-8">
            <input type="hidden" name="croppedImage" id="croppedImage">
            <input type="hidden" name="croppedBanner" id="croppedBanner">

            <div class="flex flex-col md:flex-row items-start md:items-center space-y-6 md:space-y-0 md:space-x-6">
                <div class="flex flex-col items-center w-full md:w-auto">
                    <div id="profileImage" class="profile-img-container mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#ddd" class="w-full h-full p-4 <?php echo !empty($profilePicPath) ? 'hidden' : ''; ?>" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                        </svg>
                        <img id="editImagePreview" src="<?php echo $profilePicPath; ?>" class="w-full h-full object-cover <?php echo empty($profilePicPath) ? 'hidden' : ''; ?>" alt="Profile Avatar">
                    </div>
                    <label for="profileUpload" class="text-sm font-bold py-2 px-4 text-purple-700 bg-purple-100 rounded-xl shadow-md hover:bg-purple-200 transition duration-150 cursor-pointer whitespace-nowrap">
                        Upload Profile Image
                        <input type="file" id="profileUpload" class="hidden" accept="image/*">
                    </label>
                </div>
                <div class="flex-grow w-full md:w-auto mt-4 md:mt-0">
                    <div class="grid grid-cols-1 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                            <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($userName); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                            <div class="flex space-x-2">
                                <input type="email" id="email" value="<?php echo htmlspecialchars($userEmail); ?>" disabled class="flex-grow px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="userBio" class="block text-sm font-medium text-gray-700 mb-1">Profile Welcome Message</label>
                <textarea id="userBio" name="userBio" rows="4" oninput="updateCharCount()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-purple-500 focus:border-purple-500" placeholder="Tell everyone a little about yourself..."><?php echo htmlspecialchars($userBio ?? ''); ?></textarea>
                <div class="text-right text-xs text-gray-500 mt-1"><span id="charCount">0</span> / 500 characters</div>
            </div>

            <hr class="border-gray-200" />

            <div>
                <h2 class="text-xl font-bold text-gray-700 mb-4">Privacy Settings</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-1">
                        <p class="text-gray-700 text-base">> Show <strong class="font-extrabold">"Your art"</strong> tab publicly</p>
                        <label class="toggle-label relative inline-flex items-center cursor-pointer"><input type="checkbox" id="toggleArt" name="toggleArt" class="sr-only peer" <?php echo ($showArt == 1) ? 'checked' : ''; ?>><span class="slider w-11 h-6 rounded-full"></span><span class="absolute left-[2px] top-[2px] w-5 h-5 rounded-full transition-all peer-checked:translate-x-5"></span></label>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <p class="text-gray-700 text-base">> Show <strong class="font-extrabold">"History"</strong> tab publicly</p>
                        <label class="toggle-label relative inline-flex items-center cursor-pointer"><input type="checkbox" id="toggleHistory" name="toggleHistory" class="sr-only peer" <?php echo ($showHistory == 1) ? 'checked' : ''; ?>><span class="slider w-11 h-6 rounded-full"></span><span class="absolute left-[2px] top-[2px] w-5 h-5 rounded-full transition-all peer-checked:translate-x-5"></span></label>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <p class="text-gray-700 text-base">> Show <strong class="font-extrabold">"Comments"</strong> tab publicly</p>
                        <label class="toggle-label relative inline-flex items-center cursor-pointer"><input type="checkbox" id="toggleComments" name="toggleComments" class="sr-only peer" <?php echo ($showComments == 1) ? 'checked' : ''; ?>><span class="slider w-11 h-6 rounded-full"></span><span class="absolute left-[2px] top-[2px] w-5 h-5 rounded-full transition-all peer-checked:translate-x-5"></span></label>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="pt-4 flex justify-center space-x-4 border-t border-gray-100">
                <button id="saveButton" type="submit" class="flex items-center px-6 py-2 bg-purple-600 text-white font-semibold rounded-xl shadow-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 13.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>Save Changes</button>
                <button type="button" onclick="location.reload()" class="flex items-center px-6 py-2 bg-gray-600 text-white font-semibold rounded-xl shadow-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>Cancel</button>
            </div>
            <div id="charLimitAlert" class="hidden mt-4 p-3 rounded-lg text-sm font-medium bg-red-100 text-red-700">
                <strong>Character limit exceeded!</strong>
            </div>
        </form>
    </div>
    <script>
        // This function now counts characters instead of words.
        function updateCharCount() {
            const bioText = document.getElementById('userBio').value;
            const saveButton = document.getElementById('saveButton');
            const charLimitAlert = document.getElementById('charLimitAlert');
            const countElement = document.getElementById('charCount');

            const charCount = bioText.length;

            countElement.textContent = charCount;

            if (charCount > 500) {
                countElement.classList.add('text-red-600', 'font-bold');
                saveButton.disabled = true;
                saveButton.classList.add('opacity-50', 'cursor-not-allowed');
                charLimitAlert.classList.remove('hidden');
            } else {
                countElement.classList.remove('text-red-600', 'font-bold');
                saveButton.disabled = false;
                saveButton.classList.remove('opacity-50', 'cursor-not-allowed');
                charLimitAlert.classList.add('hidden');
            }
        }

        const profileModal = document.getElementById('cropperModal');
        const profileImageToCrop = document.getElementById('imageToCrop');
        const profileCropButton = document.getElementById('cropButton');
        const profileFileInput = document.getElementById('profileUpload');
        let profileCropper;

        profileFileInput.addEventListener('change', (event) => {
            handleFileSelect(event, profileImageToCrop, profileModal, (cropperInstance) => {
                profileCropper = new Cropper(cropperInstance, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 0.8
                });
            });
        });

        profileCropButton.addEventListener('click', () => {
            if (!profileCropper) return;
            const canvas = profileCropper.getCroppedCanvas({
                width: 250,
                height: 250
            });
            document.getElementById('editImagePreview').src = canvas.toDataURL('image/png');
            document.querySelector('.profile-img-container svg').classList.add('hidden');
            document.getElementById('editImagePreview').classList.remove('hidden');
            document.getElementById('croppedImage').value = canvas.toDataURL('image/png');
            closeModal(profileModal, profileCropper, profileFileInput);
            profileCropper = null;
        });

        const bannerModal = document.getElementById('bannerCropperModal');
        const bannerImageToCrop = document.getElementById('bannerToCrop');
        const bannerCropButton = document.getElementById('cropBannerButton');
        const bannerFileInput = document.getElementById('bannerUpload');
        let bannerCropper;

        bannerFileInput.addEventListener('change', (event) => {
            handleFileSelect(event, bannerImageToCrop, bannerModal, (cropperInstance) => {
                bannerCropper = new Cropper(cropperInstance, {
                    aspectRatio: 16 / 9,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 1
                });
            });
        });

        bannerCropButton.addEventListener('click', () => {
            if (!bannerCropper) return;
            const canvas = bannerCropper.getCroppedCanvas({
                width: 800,
                height: 450
            });
            document.querySelector('.banner').style.backgroundImage = `url('${canvas.toDataURL('image/png')}')`;
            document.querySelector('.banner').textContent = '';
            document.getElementById('croppedBanner').value = canvas.toDataURL('image/png');
            closeModal(bannerModal, bannerCropper, bannerFileInput);
            bannerCropper = null;
        });

        function handleFileSelect(event, imgElement, modalElement, callback) {
            const file = event.target.files[0];
            if (!file || file.size > 5 * 1024 * 1024) {
                alert("File is too large (max 5MB) or invalid.");
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                imgElement.src = e.target.result;
                modalElement.classList.remove('hidden');
                callback(imgElement);
            };
            reader.readAsDataURL(file);
        }

        function closeModal(modal, cropper, input) {
            if (cropper) cropper.destroy();
            modal.classList.add('hidden');
            if (input) input.value = '';
        }

        window.closeProfileModal = () => closeModal(profileModal, profileCropper, profileFileInput);
        window.closeBannerModal = () => closeModal(bannerModal, bannerCropper, bannerFileInput);
        document.addEventListener('DOMContentLoaded', updateCharCount);
    </script>
</body>

</html>