<?php
session_start();
require_once "auth_check.php";

// This script is designed to be polled by the profile page to check for real-time rating updates.

// We need to know which profile page is asking for an update.
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($profile_user_id === 0) {
    // If no user_id is provided, assume it's the logged-in user's own profile.
    $profile_user_id = $_SESSION['user_id'] ?? 0;
}

$response = ['update' => false];

// Check if there's an updated rating stored in the session for this specific user's profile.
$session_key = 'updated_rating_for_' . $profile_user_id;
if (isset($_SESSION[$session_key])) {
    $response['update'] = true;
    $response['data'] = $_SESSION[$session_key];

    // Once the data is sent, clear it from the session to prevent repeated updates.
    unset($_SESSION[$session_key]);
}

header('Content-Type: application/json');
echo json_encode($response);
?>