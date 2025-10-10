<?php
session_start();
require_once "config.php"; // Your database connection
require "vendor/autoload.php"; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];

    // 1. Generate a secure token
    $token = bin2hex(random_bytes(16));
    $token_hash = hash("sha256", $token);
    
    // 2. Set an expiry time (e.g., 30 minutes from now)
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

    // 3. Save the hashed token and expiry to the database
    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_email = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("sss", $token_hash, $expiry, $email);
        $stmt->execute();

        // FORGOT PASSWORD LIVE HOSTING
        /* // Check if a user with that email exists
        if ($mysqli->affected_rows) {
            // 4. Send the email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->SMTPAuth = true;
                $mail->Host       = 'smtp.example.com'; // Your SMTP host (e.g., smtp.gmail.com)
                $mail->Username   = 'you@example.com'; // Your SMTP username
                $mail->Password   = 'your_app_password'; // Your SMTP password or App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('no-reply@yourdomain.com', 'BattleArt Support');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = 'Click <a href="http://yourwebsite.com/reset-password.php?token=' . $token . '">here</a> to reset your password.';

                $mail->send();

            } catch (Exception $e) {
                // You can log this error for debugging
                // error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        }
    }

    // 5. Show a generic message to prevent email enumeration
    $_SESSION['message'] = "If an account with that email exists, a password reset link has been sent.";
    header("Location: forgot-password.php");
    exit(); */

    //LOCAL HOSTING FORGOT PASSWORD
    // Check if a user with that email exists
        if ($mysqli->affected_rows) {
            
            // --- SIMULATE EMAIL FOR LOCAL DEVELOPMENT ---
            // Instead of sending an email, we will display the link on the screen.
            
            // IMPORTANT: Change 'localhost/your-project-folder' to your actual local project path
            $reset_link = "http://localhost/battleart/forgot.php?token=" . $token;

            echo "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #ccc; margin: 20px;'>
                    <h2>DEV MODE: Email Simulation</h2>
                    <p>Normally, an email would be sent. For local testing, please use the link below:</p>
                    <p><strong><a href='{$reset_link}'>Click here to reset your password</a></strong></p>
                </div>
            ";
            
            // We stop the script here so you can see and click the link.
            exit(); 
        }
    }

    // If the email was not found, we still redirect to show a generic message
    $_SESSION['message'] = "If an account with that email exists, a password reset link has been sent.";
    header("Location: forgot-password.php");
    exit();
}