<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "config.php";
require_once "auth_check.php";

requireLogin();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $challenge_id = (int)$_POST['challenge_id'];
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_text) && $challenge_id > 0) {
        $sql = "INSERT INTO comments (user_id, challenge_id, comment_text) VALUES (?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iis", $user_id, $challenge_id, $comment_text);
            
            // Execute the query and check if it was successful
            if ($stmt->execute()) {-
                // Find the ID of the user who owns the original challenge
                $owner_id = null;
                $owner_sql = "SELECT user_id FROM challenges WHERE challenge_id = ?";
                if ($owner_stmt = $mysqli->prepare($owner_sql)) {
                    $owner_stmt->bind_param("i", $challenge_id);
                    $owner_stmt->execute();
                    $result = $owner_stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $owner_id = $row['user_id'];
                    }
                    $owner_stmt->close();
                }
                //Create a notification for the owner, but not if they are commenting on their own art
                if ($owner_id && $owner_id != $user_id) {
                    $notify_sql = "INSERT INTO notifications (recipient_user_id, sender_user_id, type, target_id) VALUES (?, ?, 'comment', ?)";
                    if ($notify_stmt = $mysqli->prepare($notify_sql)) {
                        $notify_stmt->bind_param("iii", $owner_id, $user_id, $challenge_id);
                        $notify_stmt->execute();
                        $notify_stmt->close();
                    }
                }
            }
            $stmt->close();
        }
    }
    
    // Redirect back to the challenge page to see the new comment
    header("Location: challengepage.php?id=" . $challenge_id);
    exit;
}