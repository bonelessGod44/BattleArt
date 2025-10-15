<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "config.php";
require_once "auth_check.php";

//Ensure the user is logged in
requireLogin();
$user_id = $_SESSION['user_id'];

//Check if an action was sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    if ($action === 'all') {
        //Delete ALL notifications for the logged-in user
        $sql = "DELETE FROM notifications WHERE recipient_user_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } 
    elseif ($action === 'single' && isset($_POST['notification_id'])) {
        //Delete a SINGLE notification for the logged-in user
        $notification_id = (int)$_POST['notification_id'];
        //The WHERE clause is a crucial security check to ensure users can only delete their OWN notifications
        $sql = "DELETE FROM notifications WHERE notification_id = ? AND recipient_user_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ii", $notification_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$mysqli->close();

//Redirect back to the notifications page after the action is complete
header("Location: notification.php");
exit;
?>