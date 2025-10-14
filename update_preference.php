<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

requireLogin();
$user_id = $_SESSION['user_id'];

// Get the new value from the JavaScript request
$data = json_decode(file_get_contents("php://input"));
$allow_notifications = isset($data->notifications) && $data->notifications === true ? 1 : 0;

// Update the database
$sql = "UPDATE users SET allow_notifications = ? WHERE user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ii", $allow_notifications, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
$mysqli->close();
?>