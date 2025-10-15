<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$user_id = $_SESSION['user_id'];
$challenge_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($challenge_id === 0) {
    die("Invalid challenge ID.");
}

//Fetch challenge to verify ownership and get filename
$sql_fetch = "SELECT user_id, original_art_filename FROM challenges WHERE challenge_id = ?";
if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $challenge_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($challenge = $result->fetch_assoc()) {
        //SECURITY CHECK: Ensure the logged-in user is the owner
        if ($challenge['user_id'] !== $user_id) {
            die("You do not have permission to delete this challenge.");
        }

        //Delete the image file from the server
        $filePath = 'assets/uploads/' . $challenge['original_art_filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        //Delete the challenge from the database.
        //Associated interpretations, comments, and likes will be deleted automatically
        //because of the "ON DELETE CASCADE" rule in your database setup.
        $sql_delete = "DELETE FROM challenges WHERE challenge_id = ?";
        if ($stmt_delete = $mysqli->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $challenge_id);
            $stmt_delete->execute();
            $stmt_delete->close();
        }
        
    }
    $stmt_fetch->close();
}

$mysqli->close();

//Redirect to the art list with a success message
$_SESSION['message'] = "Challenge successfully deleted.";
header("Location: listofarts.php");
exit;
?>