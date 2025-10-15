<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

requireLogin();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"));
$allow_notifications = isset($data->notifications) && $data->notifications === true ? 1 : 0;

$sql = "UPDATE users SET allow_notifications = ? WHERE user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ii", $allow_notifications, $user_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database query failed.']);
    }
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database prepare failed.']);
}
$mysqli->close();
?>