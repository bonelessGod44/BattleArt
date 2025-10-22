<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

// Ensure the user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$liker_user_id = $_SESSION['user_id'];
$interpretation_id = isset($_POST['interpretation_id']) ? (int)$_POST['interpretation_id'] : 0;

if ($interpretation_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid interpretation ID']);
    exit;
}

// Check if the user has already liked this interpretation
$sql_check = "SELECT like_id FROM interpretation_likes WHERE user_id = ? AND interpretation_id = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ii", $liker_user_id, $interpretation_id);
$stmt_check->execute();
$is_liked = $stmt_check->get_result()->num_rows > 0;
$stmt_check->close();

if ($is_liked) {
    // If already liked, UNLIKE it
    $sql_toggle = "DELETE FROM interpretation_likes WHERE user_id = ? AND interpretation_id = ?";
    $userHasLiked = false;

    $stmt_toggle = $mysqli->prepare($sql_toggle);
    $stmt_toggle->bind_param("ii", $liker_user_id, $interpretation_id);
    $stmt_toggle->execute();
    $stmt_toggle->close();
} else {
    // If not liked, LIKE it
    $sql_toggle = "INSERT INTO interpretation_likes (user_id, interpretation_id) VALUES (?, ?)";
    $userHasLiked = true;

    $stmt_toggle = $mysqli->prepare($sql_toggle);
    $stmt_toggle->bind_param("ii", $liker_user_id, $interpretation_id);

    // Only send notification if the INSERT was successful
    if ($stmt_toggle->execute()) {

        // --- START NOTIFICATION LOGIC ---

        // 1. Find the author of the interpretation and the parent challenge ID
        $author_id = 0;
        $challenge_id = 0;
        $sql_get_info = "SELECT user_id, challenge_id FROM interpretations WHERE interpretation_id = ?";

        if ($stmt_get_info = $mysqli->prepare($sql_get_info)) {
            $stmt_get_info->bind_param("i", $interpretation_id);
            $stmt_get_info->execute();
            $res = $stmt_get_info->get_result();
            if ($row = $res->fetch_assoc()) {
                $author_id = (int)$row['user_id'];
                $challenge_id = (int)$row['challenge_id'];
            }
            $stmt_get_info->close();
        }

        // 2. Don't notify if the user liked their own post
        if ($author_id > 0 && $author_id != $liker_user_id) {

            // 3. Insert the notification for the author
            $notification_type = 'interpretation_like'; // This type is now valid

            // Uses sender_user_id (from your .sql) and target_parent_id (which you just added)
            $sql_notify = "INSERT INTO notifications (recipient_user_id, sender_user_id, type, target_id, target_parent_id) 
                           VALUES (?, ?, ?, ?, ?)";

            if ($stmt_notify = $mysqli->prepare($sql_notify)) {
                $stmt_notify->bind_param("iisii", $author_id, $liker_user_id, $notification_type, $interpretation_id, $challenge_id);
                $stmt_notify->execute();
                $stmt_notify->close();
            }
        }
        // --- END NOTIFICATION LOGIC ---
    }
    $stmt_toggle->close();
}

// Get the new total like count
$sql_count = "SELECT COUNT(*) as like_count FROM interpretation_likes WHERE interpretation_id = ?";
$stmt_count = $mysqli->prepare($sql_count);
$stmt_count->bind_param("i", $interpretation_id);
$stmt_count->execute();
$newLikeCount = $stmt_count->get_result()->fetch_assoc()['like_count'];
$stmt_count->close();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'likeCount' => $newLikeCount,
    'userHasLiked' => $userHasLiked
]);

$mysqli->close();
