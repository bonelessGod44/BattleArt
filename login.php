<?php
// Include the database configuration and auth check files
require_once "config.php";
require_once "auth_check.php";

// If the user is already logged in, redirect to the profile page
if (isLoggedIn()) {
    header("location: profile.php");
    exit;
}

// Define variables and initialize with empty values
$email = $password = "";
$error_message = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email and password
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

    // If no validation errors, proceed to check credentials
    if (empty($error_message)) {
        // Prepare a select statement using your correct column names
        $sql = "SELECT user_id, user_email, user_password FROM users WHERE user_email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $stmt->store_result();

                // Check if email exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $email_from_db, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            if (session_status() == PHP_SESSION_NONE) {
                                session_start();
                            }

                            // Store data in session variables as required by auth_check.php
                            $_SESSION["user_logged_in"] = true;
                            $_SESSION["user_email"] = $email_from_db;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["login_time"] = time();
                            $_SESSION["last_activity"] = time();

                            // Redirect to profile page
                            header("location: profile.php");
                            exit();
                        } else {
                            // Password is not valid
                            $error_message = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            // Close statement
            $stmt->close();
        }
    }
    // Close connection
    $mysqli->close();
}

// Check for remember me cookie
$remembered_email = $_COOKIE['remember_user'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt - Login</title>
    <!-- bootstrap -->
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

                        <!-- Display error or success messages -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- login form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <!-- email field -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope" aria-hidden="true"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email"
                                           placeholder="Enter your email"
                                           value="<?php echo htmlspecialchars($remembered_email); ?>"
                                           required>
                                </div>
                            </div>

                            <!-- password field -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password"
                                           placeholder="Enter your password"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            onclick="togglePasswordVisibility()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- remember me & forgot password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="rememberMe" 
                                           name="rememberMe"
                                           <?php echo !empty($remembered_email) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                                <a href="./forgot.php" class="forgot-password-link">
                                    Forgot Password?
                                </a>
                            </div>

                            <!-- login button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>
                                    Sign In
                                </button>
                            </div>

                            <!-- register link -->
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

                <!-- additional links -->
                <div class="text-center mt-3">
                    <a href="./index.php" class="back-home-link">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- bootstrap 5 JS bundle -->
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
    </script>
</body>
</html>

