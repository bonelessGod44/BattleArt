<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$user_id = $_SESSION['user_id'];

// Update all unread notifications for the user to be 'read'
$sql = "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
$mysqli->close();

// Redirect back to the notifications page
header("Location: notification.php");
exit;