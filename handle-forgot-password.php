<?php
session_start();
require_once "config.php"; // Your database connection
date_default_timezone_set('Asia/Manila');

// This script only processes POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    // Generate a secure token and hash it
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    
    // Set an expiry time (30 minutes from now)
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

    // Save the hashed token and expiry to the database
    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_email = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("sss", $token_hash, $expiry, $email);
        $stmt->execute();

        // Check if a user's record was updated
        if ($mysqli->affected_rows > 0) {
            
            // --- SIMULATE EMAIL FOR LOCAL DEVELOPMENT ---
            // IMPORTANT: Change 'localhost/your-project-folder' to your actual local project path
            $reset_link = "http://localhost/battleart/reset-password.php?token=" . $token;

            // Store the success message with the link in the session
            $_SESSION['message'] = "
                <div class='alert alert-success'>
                    <strong>DEV MODE: Email Simulation</strong><br>
                    Normally, an email would be sent. For local testing, please use the link below:<br>
                    <a href='{$reset_link}'>Click here to reset your password</a>
                </div>
            ";

        } else {
             // If no user was found, store a generic info message in the session
             $_SESSION['message'] = "<div class='alert alert-info'>If an account with that email exists, a password reset link has been sent.</div>";
        }
        $stmt->close();
    }
}

// Redirect back to the forgot password form to display the message
header("Location: forgot.php");
exit();