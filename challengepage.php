<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

//SECURITY & DATA VALIDATION
requireLogin();

$challenge_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($challenge_id === 0) {
    die("Error: Invalid Challenge ID provided.");
}

$user_id = $_SESSION['user_id']; // Get current user's ID

//FETCH DATA FROM DATABASE
$challenge = null;
$sql = "SELECT c.*, u.user_userName 
        FROM challenges c 
        JOIN users u ON c.user_id = u.user_id 
        WHERE c.challenge_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $challenge = $result->fetch_assoc();
    } else {
        die("Challenge not found.");
    }
    $stmt->close();
}

//Check if the current user has liked this challenge
$like_check_sql = "SELECT like_id FROM likes WHERE user_id = ? AND challenge_id = ?";
$like_stmt_check = $mysqli->prepare($like_check_sql);
$like_stmt_check->bind_param("ii", $user_id, $challenge_id);
$like_stmt_check->execute();
$userHasLiked = $like_stmt_check->get_result()->num_rows > 0;
$like_stmt_check->close();

//Fetch the total number of likes
$like_sql = "SELECT COUNT(*) as like_count FROM likes WHERE challenge_id = ?";
$like_stmt = $mysqli->prepare($like_sql);
$like_stmt->bind_param("i", $challenge_id);
$like_stmt->execute();
$like_count = $like_stmt->get_result()->fetch_assoc()['like_count'];
$like_stmt->close();

//Fetch all comments for this challenge
$comments_sql = "SELECT c.*, u.user_userName, u.user_profile_pic FROM comments c JOIN users u ON c.user_id = u.user_id WHERE c.challenge_id = ? ORDER BY c.created_at DESC";
$comments_stmt = $mysqli->prepare($comments_sql);
$comments_stmt->bind_param("i", $challenge_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $comments[] = $row;
}
$comments_stmt->close();

//Fetch interpretations for this challenge
$interpretations = [];
$total_interpretations = 0;

//First, get the total count of interpretations for this challenge
$count_sql = "SELECT COUNT(*) as total FROM interpretations WHERE challenge_id = ?";
if ($count_stmt = $mysqli->prepare($count_sql)) {
    $count_stmt->bind_param("i", $challenge_id);
    $count_stmt->execute();
    $total_interpretations = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
}

//Fetch only the first 6 interpretations to display on this page
if ($total_interpretations > 0) {
    $interp_sql = "SELECT i.*, u.user_userName FROM interpretations i JOIN users u ON i.user_id = u.user_id WHERE i.challenge_id = ? ORDER BY i.created_at DESC LIMIT 6";
    if ($interp_stmt = $mysqli->prepare($interp_sql)) {
        $interp_stmt->bind_param("i", $challenge_id);
        $interp_stmt->execute();
        $interp_result = $interp_stmt->get_result();
        while ($row = $interp_result->fetch_assoc()) {
            $interpretations[] = $row;
        }
        $interp_stmt->close();
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($challenge['challenge_name']); ?> - Art Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <link rel="stylesheet" href="assets/css/navbar.css">

    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
            --text-dark: #333;
        }

        body {
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: var(--text-dark);
        }

        .comment-card {
            background-color: #f5f5f5;
        }

        .interpretation-card {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body class="font-sans text-gray-800">
    <?php require 'partials/navbar.php'; ?>
    <div class="container mx-auto p-8 my-8">

        <div class="card p-8 mb-8">
            <div class="text-center mb-6">
                <h1 class="text-4xl font-extrabold text-gray-800"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h1>
                <p class="text-gray-500 font-light mt-2">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
            </div>

            <div class="flex justify-center mb-6"> <img class="w-full max-w-xl h-auto max-h-96 object-contain rounded-lg shadow-md" src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>">
            </div>
            <div class="flex justify-between items-center text-gray-600">
                <div class="flex items-center space-x-4">
                    <a href="#" id="likeButton" data-challenge-id="<?php echo $challenge_id; ?>" class="text-sm text-gray-600 hover:text-red-500 transition-colors">
                        <i id="likeIcon" class="fas fa-heart <?php echo $userHasLiked ? 'text-red-500' : 'text-gray-400'; ?> mr-1"></i>
                        <span id="likeCount"><?php echo $like_count; ?></span> Likes
                    </a>
                    <span class="text-sm"><i class="fas fa-comment mr-1"></i> <?php echo count($comments); ?> Comments</span>
                </div>
                <a href="create_interpretation.php?challenge_id=<?php echo $challenge['challenge_id']; ?>" class="bg-purple-600 text-white py-2 px-6 rounded-full font-bold shadow-lg hover:bg-purple-700 transition-colors duration-300">
                    Challenge this Art
                </a>
            </div>
            <p class="text-gray-600 mt-6">
                <?php echo nl2br(htmlspecialchars($challenge['challenge_description'])); ?>
            </p>
        </div>

        <div class="card p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Interpretations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php if (empty($interpretations)): ?>
                    <p class="text-gray-500 col-span-full">Be the first to challenge this art and submit an interpretation!</p>
                <?php else: ?>
                    <?php foreach ($interpretations as $interp): ?>
                        <div class="interpretation-card p-4 rounded-lg shadow max-w-sm">
                            <div class="flex items-center space-x-3 mb-2">
                                <i class="fas fa-user-circle text-2xl text-purple-600"></i>
                                <span class="font-bold"><?php echo htmlspecialchars($interp['user_userName']); ?></span>
                            </div>
                            <img class="w-full h-auto rounded-lg shadow-sm mb-3" src="assets/uploads/<?php echo htmlspecialchars($interp['art_filename']); ?>" alt="Interpretation by <?php echo htmlspecialchars($interp['user_userName']); ?>">
                            <p class="text-gray-700 text-sm italic"><?php echo htmlspecialchars($interp['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <?php if ($total_interpretations > 6): ?>
                <div class="text-center mt-8">
                    <a href="all_interpretations.php?challenge_id=<?php echo $challenge_id; ?>" class="bg-gray-200 text-gray-700 py-2 px-6 rounded-full font-bold hover:bg-gray-300 transition">
                        View all <?php echo $total_interpretations; ?> interpretations...
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Comments</h2>

            <form action="post_comment.php" method="post" class="mb-6">
                <input type="hidden" name="challenge_id" value="<?php echo $challenge_id; ?>">
                <textarea name="comment_text" rows="3" class="w-full p-2 border rounded-lg focus:ring-purple-500 focus:border-purple-500" placeholder="Write a comment..." required></textarea>
                <button type="submit" class="mt-2 bg-purple-600 text-white py-2 px-4 rounded-lg font-bold hover:bg-purple-700">Post Comment</button>
            </form>

            <div class="space-y-4">
                <?php if (empty($comments)): ?>
                    <p class="text-gray-500">No comments yet. Be the first to leave a comment!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php
                        // Determine the correct avatar path for each commenter
                        $commenterAvatarPath = !empty($comment['user_profile_pic'])
                            ? 'assets/uploads/' . htmlspecialchars($comment['user_profile_pic'])
                            : 'assets/images/default-avatar.png'; // Fallback to a default avatar
                        ?>
                        <div class="comment-card p-4 rounded-lg shadow">
                            <div class="flex items-center space-x-3 mb-2">
                                <img src="<?php echo $commenterAvatarPath; ?>" alt="<?php echo htmlspecialchars($comment['user_userName']); ?>'s avatar" class="w-10 h-10 rounded-full object-cover">
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($comment['user_userName']); ?></span>
                            </div>
                            <p class="text-gray-700 text-sm"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const likeButton = document.getElementById('likeButton');
            if (!likeButton) return;

            likeButton.addEventListener('click', function(event) {
                event.preventDefault();

                const challengeId = this.dataset.challengeId;
                const likeIcon = document.getElementById('likeIcon');
                const likeCountSpan = document.getElementById('likeCount');

                const formData = new FormData();
                formData.append('challenge_id', challengeId);

                fetch('handle_like.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            likeCountSpan.textContent = data.likeCount;
                            if (data.userHasLiked) {
                                likeIcon.classList.remove('text-gray-400');
                                likeIcon.classList.add('text-red-500');
                            } else {
                                likeIcon.classList.remove('text-red-500');
                                likeIcon.classList.add('text-gray-400');
                            }
                        } else {
                            console.error('Error:', data.error);
                            alert('An error occurred. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                    });
            });
        });
    </script>
</body>

</html>