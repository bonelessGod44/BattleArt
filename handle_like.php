<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "config.php";
require_once "auth_check.php";

// Ensure the user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$challenge_id = isset($_POST['challenge_id']) ? (int)$_POST['challenge_id'] : 0;

if ($challenge_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid challenge ID']);
    exit;
}

// Check if the user has already liked this challenge
$sql_check = "SELECT like_id FROM likes WHERE user_id = ? AND challenge_id = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ii", $user_id, $challenge_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$is_liked = $result->num_rows > 0;
$stmt_check->close();

if ($is_liked) {
    // If already liked, UNLIKE it (delete the row)
    $sql_toggle = "DELETE FROM likes WHERE user_id = ? AND challenge_id = ?";
    $userHasLiked = false;
} else {
    // If not liked, LIKE it (insert a new row)
    $sql_toggle = "INSERT INTO likes (user_id, challenge_id) VALUES (?, ?)";
    $userHasLiked = true;
    
    // Get the original challenge owner's ID
    $owner_id_sql = "SELECT user_id FROM challenges WHERE challenge_id = ?";
    $owner_stmt = $mysqli->prepare($owner_id_sql);
    $owner_stmt->bind_param("i", $challenge_id);
    $owner_stmt->execute();
    $owner_id = $owner_stmt->get_result()->fetch_assoc()['user_id'];
    $owner_stmt->close();
    
    // Create a notification for the challenge owner
    if ($owner_id != $user_id) {
        $notify_sql = "INSERT INTO notifications (recipient_user_id, sender_user_id, type, target_id) VALUES (?, ?, 'like', ?)";
        $notify_stmt = $mysqli->prepare($notify_sql);
        $notify_stmt->bind_param("iii", $owner_id, $user_id, $challenge_id);
        $notify_stmt->execute();
        $notify_stmt->close();
    }
}

$stmt_toggle = $mysqli->prepare($sql_toggle);
$stmt_toggle->bind_param("ii", $user_id, $challenge_id);
$stmt_toggle->execute();
$stmt_toggle->close();

// Get the new total like count for the challenge
$sql_count = "SELECT COUNT(*) as like_count FROM likes WHERE challenge_id = ?";
$stmt_count = $mysqli->prepare($sql_count);
$stmt_count->bind_param("i", $challenge_id);
$stmt_count->execute();
$newLikeCount = $stmt_count->get_result()->fetch_assoc()['like_count'];
$stmt_count->close();

// Send back a JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'likeCount' => $newLikeCount,
    'userHasLiked' => $userHasLiked
]);

$mysqli->close();
?>