<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$user_id = $_SESSION['user_id'];

if (isset($_GET['notification_id']) && !empty($_GET['notification_id'])) {
    $notification_id = $_GET['notification_id'];
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND recipient_user_id = ?";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $destination = isset($_GET['destination']) ? $_GET['destination'] : 'notifications.php';
    header("Location: " . $destination);
    exit;

} else {
    $sql = "UPDATE notifications SET is_read = 1 WHERE recipient_user_id = ? AND is_read = 0";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: notification.php");
    exit;
}
