<?php
session_start();
require_once "config.php";

$token = $_GET["token"] ?? null;

if ($token === null) {
    die("No token provided.");
}

$token_hash = hash("sha256", $token);

// Find the user by the token hash and check if it has not expired
$sql = "SELECT * FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if ($user === null) {
    die("Token not found or has expired.");
}

// Handle form submission to update the password
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate password and confirmation
    if ($_POST["password"] !== $_POST["password_confirmation"]) {
        die("Passwords must match.");
    }

    // Hash the new password
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Update the user's password and clear the reset token
    $update_sql = "UPDATE users SET user_password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?";
    if ($update_stmt = $mysqli->prepare($update_sql)) {
        $update_stmt->bind_param("si", $password_hash, $user["user_id"]);
        $update_stmt->execute();
        $update_stmt->close();

        echo "Password updated successfully. You can now <a href='login.php'>log in</a>.";
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BattleArt - Forgot Password</title>
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
            
            <!-- page header -->
            <div class="text-center mb-4">
              <h1 class="brand-title fw-bold text-primary mb-2">BattleArt</h1>
              <p class="subtitle text-muted">Reset your password and get back to creating!</p>
            </div>

            <!-- forgot password form -->
            <form>
              <!-- email field -->
              <div class="mb-3">
                <label for="email" class="form-label">Registered Email Address</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                  </span>
                  <input type="email" 
                         class="form-control" 
                         id="email" 
                         name="email"
                         placeholder="Enter your registered email"
                         required>
                </div>
              </div>

              <!-- submit button -->
              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-send me-2" aria-hidden="true"></i>
                  Send Reset Link
                </button>
              </div>

              <!-- back to login link -->
              <div class="text-center">
                <p class="mb-0">
                  Remembered your password? 
                  <a href="login.php" class="login-link">
                    Back to Login
                  </a>
                </p>
              </div>
            </form>
          </div>
        </div>

        <!-- back to home -->
        <div class="text-center mt-3">
          <a href="index.html" class="back-home-link">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Back to Home
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>