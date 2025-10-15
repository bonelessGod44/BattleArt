<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$user_id = $_SESSION['user_id'];
$message = "";

// --- Handle form submissions ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    switch ($_POST['action']) {
        // --- SAVE PROFILE INFORMATION ---
        case 'save_profile':
            $firstName = trim($_POST['firstName']);
            $middleName = trim($_POST['middleName']);
            $lastName = trim($_POST['lastName']);
            $dob = trim($_POST['dob']);
            // Add age validation
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            if ($birthDate > $today) {
                $_SESSION['message'] = ['text' => 'Birth date cannot be in the future.', 'type' => 'danger'];
            } elseif ($birthDate->diff($today)->y < 13) {
                $_SESSION['message'] = ['text' => 'You must be at least 13 years old.', 'type' => 'danger'];
            } else {
                $sql = "UPDATE users SET user_firstName = ?, user_middleName = ?, user_lastName = ?, user_dob = ? WHERE user_id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("ssssi", $firstName, $middleName, $lastName, $dob, $user_id);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = ['text' => 'Profile information saved successfully!', 'type' => 'success'];
                    }
                    $stmt->close();
                }
            }
            break;

        // --- CHANGE PASSWORD ---
        case 'change_password':
            $currentPassword = $_POST['currentPassword'];
            $newPassword = $_POST['newPassword'];
            $confirmPassword = $_POST['confirmPassword'];

            if (strlen($newPassword) < 6) {
                $_SESSION['message'] = ['text' => 'New password must be at least 6 characters long.', 'type' => 'danger'];
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['message'] = ['text' => 'New passwords do not match.', 'type' => 'danger'];
            } else {
                $sql_pass = "SELECT user_password FROM users WHERE user_id = ?";
                if ($stmt_pass = $mysqli->prepare($sql_pass)) {
                    $stmt_pass->bind_param("i", $user_id);
                    $stmt_pass->execute();
                    $stmt_pass->bind_result($hashed_password);
                    if ($stmt_pass->fetch() && password_verify($currentPassword, $hashed_password)) {
                        $stmt_pass->close();
                        $new_hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
                        $sql_update = "UPDATE users SET user_password = ? WHERE user_id = ?";
                        if ($stmt_update = $mysqli->prepare($sql_update)) {
                            $stmt_update->bind_param("si", $new_hashed_password, $user_id);
                            $stmt_update->execute();
                            $_SESSION['message'] = ['text' => 'Password updated successfully!', 'type' => 'success'];
                            $stmt_update->close();
                        }
                    } else {
                        $_SESSION['message'] = ['text' => 'Incorrect current password.', 'type' => 'danger'];
                    }
                }
            }
            break;

        // --- DELETE ACCOUNT ---
        case 'delete_account':
            $sql_delete = "DELETE FROM users WHERE user_id = ?";
            if ($stmt_delete = $mysqli->prepare($sql_delete)) {
                $stmt_delete->bind_param("i", $user_id);
                $stmt_delete->execute();
                // After deleting, log the user out completely
                session_unset();
                session_destroy();
                header("location: login.php?deleted=true");
                exit;
            }
            break;
    }
    // Redirect to the same page to prevent form resubmission
    header("Location: settings.php");
    exit;
}

// --- Fetch current user data to populate the form ---
$user = null;
$sql = "SELECT user_id, user_firstName, user_middleName, user_lastName, user_dob, allow_notifications FROM users WHERE user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}
if (!$user) die("Could not load user data.");

// Check for a feedback message from the session
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
    <title>User Settings - BattleArt Style</title>
    <!-- Load Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Load Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLMDJ8uRjHCEg3MhXz4mDofKqjH44uWIX+LLMDJ8uRjHCEg3MhXz4mDofKqjH447Q9kFq71jH5H7R8S/3Qx7lR5F/7l5Q7l5R5Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Define the color scheme using the user-provided variables */
        :root {
            --primary-bg: #8c76ec;
            /* Used for Navbar/Primary Accent */
            --secondary-bg: #a6e7ff;
            /* Used for Background End */
            --light-purple: #c3b4fc;
            --dark-purple-border: #7b68ee;
            /* Used for Buttons/Borders */
            --text-dark: #333;
            --text-light: #ffffff;
            /* Calculated for Bootstrap components based on provided primary color */
            --bs-primary: var(--primary-bg);
            --bs-primary-rgb: 140, 118, 236;
            --bs-danger: #DC3545;
            font-family: 'Inter', sans-serif;
        }

        /* Full Background Gradient */
        body {
            background: linear-gradient(135deg, var(--light-purple) 0%, var(--secondary-bg) 100%);
            color: var(--text-dark);
            /* Default text color */
            min-height: 100vh;
            padding-top: 50px;
            padding-bottom: 50px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* Custom Navbar */
        .custom-navbar {
            background-color: var(--primary-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 0.5rem 1rem;
        }

        /* Navbar link styles */
        .navbar-brand,
        .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            padding: 0.5rem 0.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-brand .fa-house {
            font-size: 1.25rem;
            margin-right: 0.25rem;
        }

        /* Main White Container */
        .settings-container {
            max-width: 900px;
            width: 95%;
            background-color: #ffffff;
            border-radius: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 3rem 3rem;
            margin-top: 5rem;
        }

        /* Header styling */
        .settings-header h1 {
            color: var(--dark-purple-border) !important;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .settings-header .fa-cog {
            color: var(--bs-primary) !important;
        }

        /* Input Fields */
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.75rem;
        }

        .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.35);
            background-color: #ffffff;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            border-radius: 0.75rem;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-primary:hover {
            background-color: var(--dark-purple-border);
            border-color: var(--dark-purple-border);
            transform: translateY(-1px);
        }

        /* Danger button (Logout/Delete) */
        .btn-danger {
            background-color: var(--bs-danger);
            border-color: var(--bs-danger);
        }

        /* About button (using secondary color for contrast but not danger) */
        .btn-info {
            /* Changed to info for a distinct, non-danger look */
            background-color: #17a2b8;
            /* A nice blue/cyan color */
            border-color: #17a2b8;
            border-radius: 0.75rem;
            color: var(--text-light);
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #138496;
            transform: translateY(-1px);
        }

        /* Section Titles */
        .section-title {
            color: var(--dark-purple-border);
            font-weight: 600;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .settings-container {
                padding: 1.5rem;
                border-radius: 1.5rem;
                margin-top: 3rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'partials/navbar.php'; ?>

    <!-- Main Content Card -->
    <div class="settings-container">
        <header class="mb-5 settings-header text-center">
            <h1><i class="fas fa-cog me-3"></i>User Account Settings</h1>
            <p class="fs-6 text-muted">User ID: <?php echo htmlspecialchars($user['user_id']); ?></p>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="mb-5">
            <h2 class="section-title h5">Personal Information</h2>
            <form action="settings.php" method="post">
                <input type="hidden" name="action" value="save_profile">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="first-name" class="form-label fw-semibold">First Name</label>
                        <input type="text" id="first-name" name="firstName" class="form-control" value="<?php echo htmlspecialchars($user['user_firstName']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="middle-name" class="form-label fw-semibold">Middle Name</label>
                        <input type="text" id="middle-name" name="middleName" class="form-control" value="<?php echo htmlspecialchars($user['user_middleName']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="last-name" class="form-label fw-semibold">Last Name</label>
                        <input type="text" id="last-name" name="lastName" class="form-control" value="<?php echo htmlspecialchars($user['user_lastName']); ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="birthday" class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" id="birthday" name="dob" class="form-control" value="<?php echo htmlspecialchars($user['user_dob']); ?>" readonly>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 py-2"><i class="fas fa-save me-2"></i>Save Profile</button>
                </div>
            </form>
        </div>

        <div class="mb-5">
            <h2 class="section-title h5">Account Security</h2>
            <form action="settings.php" method="post">
                <input type="hidden" name="action" value="change_password">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="current-password" class="form-label fw-semibold">Current Password</label>
                        <input type="password" id="current-password" name="currentPassword" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="new-password" class="form-label fw-semibold">New Password</label>
                        <input type="password" id="new-password" name="newPassword" class="form-control" minlength="6" required>
                    </div>
                    <div class="col-md-4">
                        <label for="confirm-password" class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirmPassword" class="form-control" required>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 py-2"><i class="fas fa-key me-2"></i>Update Password</button>
                </div>
            </form>
        </div>

        <div class="mb-5">
            <h2 class="section-title h5">Preferences & Notifications</h2>
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                <div>
                    <p class="mb-1 fw-semibold text-dark">Email and Push Notifications</p>
                    <small class="text-muted">Receive alerts about new challenges, comments, and site updates.</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="notification-toggle" onchange="toggleNotifications(event)" <?php echo ($user['allow_notifications'] == 1) ? 'checked' : ''; ?>>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h2 class="section-title h5">App Information</h2>
            <button onclick="showAbout()" class="btn btn-info w-100 py-2">
                <i class="fas fa-info-circle me-2"></i>
                About BattleArt App
            </button>
        </div>

        <div class="mb-3">
            <h2 class="section-title h5 text-danger">Danger Zone</h2>
            <button onclick="deleteAccount()" class="btn btn-danger w-100 py-2"><i class="fas fa-trash-alt me-2"></i>Permanently Delete Account</button>
        </div>
    </div>

    <div id="message-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMessage(title, message, isError = false) {
            const container = document.getElementById('message-container');
            const modalId = `messageModal_${Date.now()}`; // Unique ID to prevent conflicts

            const modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title ${isError ? 'text-danger' : 'text-primary'}">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-2 pb-4">
                            <p class="text-muted">${message.replace(/\n/g, '<br>')}</p>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-primary w-100 rounded-pill" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
            container.innerHTML = modalHTML;

            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Clean up the modal from the DOM after it's hidden to prevent clutter
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }

        function showConfirmationModal(title, message, confirmText, confirmVariant, callback) {
            const container = document.getElementById('message-container');
            const modalId = 'confirmModal';

            // Remove any previous confirm modal to be safe
            const existingModal = document.getElementById(modalId);
            if (existingModal) existingModal.remove();

            const modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title text-${confirmVariant}">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-2 pb-4">
                            <p class="text-muted">${message}</p>
                        </div>
                        <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmActionBtn" class="btn btn-${confirmVariant} rounded-pill flex-grow-1">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
            container.innerHTML = modalHTML;

            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // When the confirm button is clicked, hide the modal and run the callback function
            document.getElementById('confirmActionBtn').onclick = () => {
                modal.hide();
                callback(); // Execute the provided action (e.g., performLogout, performDelete)
            };

            // Clean up after the modal is hidden
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }

        function toggleNotifications(event) {
            const isEnabled = event.target.checked;

            // Send the update to the server
            fetch('update_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notifications: isEnabled
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage("Preferences", `Notifications have been turned ${isEnabled ? "ON" : "OFF"}.`);
                        const badge = document.querySelector('.notification-badge');

                        if (!isEnabled && badge) {
                            badge.style.display = 'none';
                        } else if (isEnabled) {
                            window.location.reload();
                        }

                    } else {
                        showMessage("Error", "Could not save preference.", true);
                        event.target.checked = !isEnabled;
                    }
                });
        }

        function handleLogout() {
            showConfirmationModal("Confirm Logout", "Are you sure you want to log out?", "Logout", "danger", () => {
                window.location.href = 'logout.php';
            });
        }

        function deleteAccount() {
            showConfirmationModal("Danger: Delete Account", "This is permanent. Are you sure?", "Delete Permanently", "danger", () => {
                // Create a hidden form and submit it to perform the delete action securely
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'settings.php';
                form.innerHTML = `<input type="hidden" name="action" value="delete_account">`;
                document.body.appendChild(form);
                form.submit();
            });
        }

        // Mock functions that can be replaced or removed
        function manageSubscription() {
            showMessage("Subscription", "Redirecting to manage your plan... (Mock)");
        }

        function showAbout() {
            showMessage("About BattleArt",
                "BattleArt App Version 1.2.0\n\n© 2024 Art & Tech Inc. All rights reserved.\nThis app is designed to connect artists with creative challenges.",
                false);
        }
    </script>
</body>

</html>