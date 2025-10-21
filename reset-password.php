<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "config.php";
date_default_timezone_set('Asia/Manila');

// This block of code runs first to validate the token from the URL
$token = $_GET["token"] ?? null;

if ($token === null) {
    die("Error: No token provided. Please start the process from the forgot password page.");
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
    die("Error: Token not found or has expired. Please request a new link.");
}

// This block of code runs ONLY when the user submits the new password form
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["password"] !== $_POST["password_confirmation"]) {
        $message = "<div class='alert alert-danger'>Error: Passwords must match.</div>";
    } else {
        $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

        // Update the user's password and clear the reset token fields
        $update_sql = "UPDATE users SET user_password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?";
        if ($update_stmt = $mysqli->prepare($update_sql)) {
            $update_stmt->bind_param("si", $password_hash, $user["user_id"]);
            $update_stmt->execute();
            
            // Display a success message and stop the script
            echo "
                <div class='container mt-5'>
                    <div class='alert alert-success'>
                        Password updated successfully. You can now <a href='login.php'>log in</a>.
                    </div>
                </div>
            ";
            exit(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt - Reset Password</title>
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
                            <p class="subtitle text-muted">Set your new password.</p>
                        </div>

                        <?php if (!empty($message)) echo $message; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i> Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>