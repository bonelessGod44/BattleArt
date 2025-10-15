<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$rater_user_id = $_SESSION['user_id'];
$rated_user_id = isset($_POST['rated_user_id']) ? (int)$_POST['rated_user_id'] : 0;
$rating_value = isset($_POST['rating_value']) ? (float)$_POST['rating_value'] : 0;

//Basic validation
if ($rated_user_id === 0 || $rating_value <= 0 || $rating_value > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid data provided.']);
    exit;
}

//Prevent users from rating themselves
if ($rater_user_id === $rated_user_id) {
    echo json_encode(['success' => false, 'error' => 'You cannot rate yourself.']);
    exit;
}

//Use INSERT...ON DUPLICATE KEY UPDATE to create or update a rating
$sql = "INSERT INTO ratings (rated_user_id, rater_user_id, rating_value) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE rating_value = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("iidi", $rated_user_id, $rater_user_id, $rating_value, $rating_value);
    $stmt->execute();
    $stmt->close();

    //After saving, calculate the new average rating and total count
    $avg_sql = "SELECT AVG(rating_value) as avg_rating, COUNT(*) as rating_count FROM ratings WHERE rated_user_id = ?";
    if ($avg_stmt = $mysqli->prepare($avg_sql)) {
        $avg_stmt->bind_param("i", $rated_user_id);
        $avg_stmt->execute();
        $result = $avg_stmt->get_result()->fetch_assoc();
        $newAverage = round($result['avg_rating'], 1);
        $newCount = $result['rating_count'];
        $avg_stmt->close();

        //Send back the new data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'newAverage' => $newAverage,
            'newCount' => $newCount
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
$mysqli->close();
?>
