<?php
// Include the database configuration and auth check files
session_start();
require_once "config.php"; // This must define the $mysqli object
require_once "auth_check.php";

$email = $password = "";
$error_message = "";
$is_banned = false; // Flag to trigger the modal

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["email"]))) {
        $error_message = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $error_message = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($error_message)) {

        // MODIFIED SQL: Fetch account_status
        $sql = "SELECT user_id, user_email, user_password, user_type, user_userName, user_profile_pic, account_status
                FROM users
                WHERE user_email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    // MODIFIED BIND: Add $account_status
                    $stmt->bind_result($id, $email_from_db, $hashed_password, $user_type, $username, $profile_pic, $account_status);

                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {

                            // *** BAN CHECK ADDED HERE ***
                            if ($account_status === 'banned') {
                                $is_banned = true; // Set flag to show modal
                                $error_message = "Your account has been banned."; // Optional: Set a general error too
                            } else {
                                // --- Login Successful (User is not banned) ---
                                session_regenerate_id(true);

                                $_SESSION["user_logged_in"] = true;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["user_email"] = $email_from_db;
                                $_SESSION["user_type"] = $user_type;
                                $_SESSION["username"] = $username;
                                $_SESSION["profile_pic"] = $profile_pic;
                                $_SESSION["login_time"] = time();
                                $_SESSION["last_activity"] = time();

                                $mysqli->query("UPDATE users SET last_seen = NOW() WHERE user_id = $id");

                                if (!empty($_POST['rememberMe'])) {
                                    setcookie('remember_user', $email, time() + (86400 * 30), "/");
                                } else {
                                    setcookie('remember_user', '', time() - 3600, "/");
                                }

                                if (strtolower($user_type) == 'admin') {
                                    header("location: admin_dashboard.php");
                                } else {
                                    if (isset($_SESSION['redirect_after_login'])) {
                                        $redirect_url = $_SESSION['redirect_after_login'];
                                        unset($_SESSION['redirect_after_login']);
                                        header('Location: ' . $redirect_url);
                                    } else {
                                        header("location: profile.php");
                                    }
                                }
                                exit();
                                // --- End Successful Login ---
                            }
                        } else {
                            $error_message = "Invalid email or password.";
                        }
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    // Do not close $mysqli here
}

$remembered_email = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid vh-100">
        <div class="row h-100 justify-content-center align-items-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">

                        <div class="text-center mb-4">
                            <h1 class="brand-title fw-bold text-primary mb-2">BattleArt</h1>
                            <p class="subtitle text-muted">Sign in to join art battles & reimagine originals</p>
                        </div>

                        <?php // Keep general error message display, but hide if it's the ban message
                        if (!empty($error_message) && !$is_banned) : ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope" aria-hidden="true"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($remembered_email); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" onclick="togglePasswordVisibility()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe" <?php echo !empty($remembered_email) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                <a href="./forgot.php" class="forgot-password-link">
                                    Forgot Password?
                                </a>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                                    Sign In
                                </button>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">Don't have an account?
                                    <a href="./register.php" class="signup-link">
                                        Sign up here
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="./index.php" class="back-home-link">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ADDED: Banned User Modal -->
    <div class="modal fade" id="bannedModal" tabindex="-1" aria-labelledby="bannedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bannedModalLabel"><i class="bi bi-slash-circle-fill me-2"></i>Account Banned</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your account has been banned due to a violation of our terms of service.</p>
                    <p>If you believe this is an error, please contact support for assistance.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:support@battleart.com" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
    <!-- End Banned User Modal -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }

        // ADDED: JavaScript to show the modal if the user is banned
        <?php if ($is_banned): ?>
            document.addEventListener('DOMContentLoaded', (event) => {
                var bannedModal = new bootstrap.Modal(document.getElementById('bannedModal'));
                bannedModal.show();
            });
        <?php endif; ?>
    </script>
</body>

</html>