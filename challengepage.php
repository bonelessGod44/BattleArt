<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

// Get the challenge ID from the URL (publicly accessible)
$challenge_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($challenge_id === 0) {
    die("Error: Invalid Challenge ID provided.");
}

// Check if a user is logged in. This will be null for guests.
$viewer_user_id = $_SESSION['user_id'] ?? null;

//FETCH ALL PUBLIC DATA FOR THE PROFILE PAGE 
$challenge = null;
$sql = "SELECT c.*, u.user_userName, u.user_profile_pic FROM challenges c JOIN users u ON c.user_id = u.user_id WHERE c.challenge_id = ?";
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

$author_id = $challenge['user_id'];
$current_user_rating = 0;
if ($viewer_user_id) {
    $rating_sql = "SELECT rating_value FROM ratings WHERE rater_user_id = ? AND rated_user_id = ?";
    if ($rating_stmt = $mysqli->prepare($rating_sql)) {
        $rating_stmt->bind_param("ii", $viewer_user_id, $author_id);
        $rating_stmt->execute();
        $res = $rating_stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $current_user_rating = $row['rating_value'];
        }
        $rating_stmt->close();
    }
}

$userHasLiked = false;
if ($viewer_user_id) {
    $like_check_sql = "SELECT like_id FROM likes WHERE user_id = ? AND challenge_id = ?";
    if ($like_stmt_check = $mysqli->prepare($like_check_sql)) {
        $like_stmt_check->bind_param("ii", $viewer_user_id, $challenge_id);
        $like_stmt_check->execute();
        $userHasLiked = $like_stmt_check->get_result()->num_rows > 0;
        $like_stmt_check->close();
    }
}

$like_sql = "SELECT COUNT(*) as like_count FROM likes WHERE challenge_id = ?";
$like_stmt = $mysqli->prepare($like_sql);
$like_stmt->bind_param("i", $challenge_id);
$like_stmt->execute();
$like_count = $like_stmt->get_result()->fetch_assoc()['like_count'];
$like_stmt->close();

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

$interpretations = [];
$total_interpretations = 0;
$count_sql = "SELECT COUNT(*) as total FROM interpretations WHERE challenge_id = ?";
if ($count_stmt = $mysqli->prepare($count_sql)) {
    $count_stmt->bind_param("i", $challenge_id);
    $count_stmt->execute();
    $total_interpretations = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
}
if ($total_interpretations > 0) {
    $interp_sql = "SELECT 
                        i.*, 
                        u.user_userName, 
                        u.user_profile_pic,
                        (SELECT COUNT(*) FROM interpretation_likes WHERE interpretation_id = i.interpretation_id) as like_count,
                        (SELECT COUNT(*) FROM interpretation_likes WHERE interpretation_id = i.interpretation_id AND user_id = ?) as user_has_liked
                   FROM interpretations i 
                   JOIN users u ON i.user_id = u.user_id 
                   WHERE i.challenge_id = ? 
                   ORDER BY i.created_at DESC 
                   LIMIT 6";

    if ($interp_stmt = $mysqli->prepare($interp_sql)) {
        $viewer_id_for_query = $viewer_user_id ?? 0;
        $interp_stmt->bind_param("ii", $viewer_id_for_query, $challenge_id);
        $interp_stmt->execute();
        $interp_result = $interp_stmt->get_result();
        while ($row = $interp_result->fetch_assoc()) {
            $interpretations[] = $row;
        }
        $interp_stmt->close();
    }
}

$authorAvatarPath = !empty($challenge['user_profile_pic'])
    ? 'assets/uploads/' . htmlspecialchars($challenge['user_profile_pic'])
    : 'assets/images/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($challenge['challenge_name']); ?> - Art Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding-top: 20px;
        }

        .card {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .btn-primary-custom {
            background-color: var(--primary-bg);
            color: white;
            border: none;
            transition: background-color 0.2s ease;
        }

        .btn-primary-custom:hover {
            background-color: #7b68ee;
            color: white;
        }

        .interpretation-card,
        .comment-card {
            background-color: #f8f9fa;
        }

        .star-rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }

        .star-rating-input input {
            display: none;
        }

        .star-rating-input label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 0.1rem;
        }

        .star-rating-input input:checked~label,
        .star-rating-input label:hover,
        .star-rating-input label:hover~label {
            color: #ffda6a;
        }
    </style>
</head>

<body class="font-sans">

    <?php require 'partials/navbar.php'; ?>

    <div class="container my-5">
        <div class="card p-4 p-md-5 mb-4">
            <div class="text-center mb-4">
                <h1 class="display-5 fw-bold text-dark"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h1>
                <a href="public_profile.php?user_id=<?php echo $challenge['user_id']; ?>" class="text-decoration-none d-inline-flex align-items-center justify-content-center">
                    <img src="<?php echo $authorAvatarPath; ?>" alt="<?php echo htmlspecialchars($challenge['user_userName']); ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                    <p class="text-muted fs-5 mb-0">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
                </a>
            </div>
            <div class="mt-3">
                <small class="text-muted d-block text-center">Rate the author!</small>
                <div class="star-rating-input" data-rated-user-id="<?php echo $author_id; ?>">
                    <input type="radio" id="star5" name="rating" value="5" <?php if ($current_user_rating == 5) echo 'checked'; ?>><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star4" name="rating" value="4" <?php if ($current_user_rating == 4) echo 'checked'; ?>><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star3" name="rating" value="3" <?php if ($current_user_rating == 3) echo 'checked'; ?>><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star2" name="rating" value="2" <?php if ($current_user_rating == 2) echo 'checked'; ?>><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star1" name="rating" value="1" <?php if ($current_user_rating == 1) echo 'checked'; ?>><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                </div>
            </div>
            <div class="text-center mb-4">
                <img class="img-fluid rounded shadow-sm" style="max-height: 500px;" src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>">
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <a href="#" id="likeButton" data-challenge-id="<?php echo $challenge_id; ?>" class="text-decoration-none text-muted">
                        <i id="likeIcon" class="fas fa-heart <?php echo $userHasLiked ? 'text-danger' : 'text-secondary'; ?> me-1"></i> <span id="likeCount"><?php echo $like_count; ?></span> Likes
                    </a>
                    <span class="text-muted"><i class="fas fa-comment me-1"></i> <?php echo count($comments); ?> Comments</span>
                </div>
                <!-- DYNAMIC BUTTONS -->
                <?php if ($viewer_user_id && $viewer_user_id == $challenge['user_id']): ?>
                    <div class="btn-group">
                        <a href="edit_challenge.php?id=<?php echo $challenge['challenge_id']; ?>" class="btn btn-outline-primary">Edit</a>
                        <button type="button" id="deleteChallengeBtn" class="btn btn-outline-danger">Delete</button>
                    </div>
                <?php elseif ($viewer_user_id): ?>
                    <a href="create_interpretation.php?challenge_id=<?php echo $challenge['challenge_id']; ?>" class="btn btn-primary-custom rounded-pill px-4 py-2 fw-bold">Challenge this Art</a>
                <?php else: ?>
                    <button type="button" id="challengeArtBtn" class="btn btn-primary-custom rounded-pill px-4 py-2 fw-bold">Challenge this Art</button>
                <?php endif; ?>
            </div>
            <hr class="my-4">
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($challenge['challenge_description'])); ?></p>
        </div>

        <div class="card p-4 p-md-5 mb-4">
            <h2 class="h4 fw-bold text-dark mb-4">Interpretations (<?php echo $total_interpretations; ?>)</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php if (empty($interpretations)): ?>
                    <div class="col-12">
                        <p class="text-muted">Be the first to challenge this art and submit an interpretation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($interpretations as $interp): ?>
                        <?php $interpAvatarPath = !empty($interp['user_profile_pic']) ? 'assets/uploads/' . htmlspecialchars($interp['user_profile_pic']) : 'assets/images/default-avatar.png'; ?>
                        <div class="col">
                            <div class="card h-100 interpretation-card" data-interpretation-id="<?php echo $interp['interpretation_id']; ?>">
                                <div class="card-body">
                                    <a href="public_profile.php?user_id=<?php echo $interp['user_id']; ?>" class="d-flex align-items-center mb-2 text-decoration-none">
                                        <img src="<?php echo $interpAvatarPath; ?>" alt="<?php echo htmlspecialchars($interp['user_userName']); ?>'s avatar" class="rounded-circle me-2" style="width: 24px; height: 24px; object-fit: cover;">
                                        <span class="fw-bold small text-dark"><?php echo htmlspecialchars($interp['user_userName']); ?></span>
                                    </a>
                                    <a href="#" class="d-block" data-bs-toggle="modal" data-bs-target="#interpretationModal"
                                        data-img-src="assets/uploads/<?php echo htmlspecialchars($interp['art_filename']); ?>"
                                        data-artist-name="<?php echo htmlspecialchars($interp['user_userName']); ?>"
                                        data-artist-avatar="<?php echo $interpAvatarPath; ?>"
                                        data-artist-id="<?php echo $interp['user_id']; ?>"
                                        data-description="<?php echo htmlspecialchars($interp['description']); ?>"
                                        data-interpretation-id="<?php echo $interp['interpretation_id']; ?>"
                                        data-like-count="<?php echo $interp['like_count']; ?>"
                                        data-user-has-liked="<?php echo $interp['user_has_liked']; ?>">
                                        <img class="img-fluid rounded mb-2" src="assets/uploads/<?php echo htmlspecialchars($interp['art_filename']); ?>" alt="Interpretation">
                                    </a>
                                    <p class="card-text small fst-italic">
                                        <?php if (!empty($interp['description'])) echo '"' . htmlspecialchars($interp['description']) . '"'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($total_interpretations > 6): ?>
                <div class="text-center mt-4"><a href="all_interpretations.php?challenge_id=<?php echo $challenge_id; ?>" class="btn btn-outline-secondary">View all <?php echo $total_interpretations; ?> interpretations</a></div>
            <?php endif; ?>
        </div>

        <div class="card p-4 p-md-5">
            <h2 class="h4 fw-bold text-dark mb-4">Comments</h2>
            <?php if ($viewer_user_id): ?>
                <form action="post_comment.php" method="post" class="mb-4">
                    <input type="hidden" name="challenge_id" value="<?php echo $challenge_id; ?>">
                    <textarea name="comment_text" rows="3" class="form-control mb-2" placeholder="Write a comment..." required></textarea>
                    <button type="submit" class="btn btn-primary-custom rounded-pill px-4">Post Comment</button>
                </form>
            <?php else: ?>
                <div class="text-center p-4 bg-light rounded-3">
                    <p class="mb-2">Want to join the conversation?</p>
                    <button type="button" id="loginToCommentBtn" class="btn btn-primary-custom rounded-pill px-4">Login to Comment</button>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-column gap-3 mt-4">
                <?php if (empty($comments)): ?>
                    <p class="text-muted">No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php $commenterAvatar = !empty($comment['user_profile_pic']) ? 'assets/uploads/' . $comment['user_profile_pic'] : 'assets/images/default-avatar.png'; ?>
                        <div class="comment-card p-3 rounded-3">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?php echo $commenterAvatar; ?>" alt="<?php echo htmlspecialchars($comment['user_userName']); ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <span class="fw-bold"><?php echo htmlspecialchars($comment['user_userName']); ?></span>
                            </div>
                            <p class="mb-0 small"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD THIS MODAL HTML -->
    <div class="modal fade" id="interpretationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div id="modalArtistInfo"></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" class="img-fluid rounded mb-3">
                    <p id="modalDescription" class="fst-italic text-muted"></p>
                </div>
                <div class="modal-footer" id="modalFooterActions">
                </div>
            </div>
        </div>
    </div>

    <div id="message-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Displays a simple "Login Required" pop-up with a link to the login page.
         */
        function showLoginPromptModal(title, message) {
            const container = document.getElementById('message-container');
            const modalId = 'loginPromptModal';
            if (document.getElementById(modalId)) document.getElementById(modalId).remove();

            const modalHTML = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow-lg border-0">
                            <div class="modal-header border-0 pb-0"><h5 class="modal-title text-primary fw-bold">${title}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body pt-2 pb-4"><p class="text-muted">${message}</p></div>
                            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1 me-2" data-bs-dismiss="modal">Cancel</button>
                                <a href="login.php" class="btn btn-primary rounded-pill flex-grow-1">Login</a>
                            </div>
                        </div>
                    </div>
                </div>`;
            container.innerHTML = modalHTML;
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
            modal._element.addEventListener('hidden.bs.modal', () => modal._element.remove());
        }

        /**
         * Displays a confirmation pop-up for dangerous actions (e.g., Delete).
         */
        function showConfirmationModal(title, message, confirmText, confirmVariant, callback) {
            const container = document.getElementById('message-container');
            const modalId = 'confirmActionModal';
            if (document.getElementById(modalId)) document.getElementById(modalId).remove();

            const modalHTML = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow-lg border-0">
                            <div class="modal-header border-0 pb-0"><h5 class="modal-title text-${confirmVariant} fw-bold">${title}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body pt-2 pb-4"><p class="text-muted">${message}</p></div>
                            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1 me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirmActionBtn" class="btn btn-${confirmVariant} rounded-pill flex-grow-1">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            container.innerHTML = modalHTML;

            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            document.getElementById('confirmActionBtn').onclick = () => {
                modal.hide();
                callback();
            };
            modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
        }

        // --- All event listeners are now inside this single block ---
        document.addEventListener('DOMContentLoaded', () => {
            const isUserLoggedIn = <?php echo json_encode(isset($viewer_user_id)); ?>;

            // Handle Like Button
            const likeButton = document.getElementById('likeButton');
            if (likeButton) {
                likeButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!isUserLoggedIn) {
                        showLoginPromptModal('Login Required', 'You must be logged in to like this art.');
                        return;
                    }

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
                                    likeIcon.classList.remove('text-secondary');
                                    likeIcon.classList.add('text-danger');
                                } else {
                                    likeIcon.classList.remove('text-danger');
                                    likeIcon.classList.add('text-secondary');
                                }
                            }
                        });
                });
            }

            // Handle "Delete Challenge" button for author
            const deleteBtn = document.getElementById('deleteChallengeBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    showConfirmationModal(
                        'Delete Challenge',
                        'This action is permanent and cannot be undone. Are you sure?',
                        'Delete Permanently',
                        'danger',
                        () => {
                            window.location.href = 'delete_challenge.php?id=<?php echo $challenge_id; ?>';
                        }
                    );
                });
            }

            // Handle "Challenge this Art" button for guests
            const challengeBtn = document.getElementById('challengeArtBtn');
            if (challengeBtn) {
                challengeBtn.addEventListener('click', () => {
                    showLoginPromptModal('Login Required', 'You need to be logged in to challenge this art.');
                });
            }

            // Handle "Login to Comment" button for guests
            const loginCommentBtn = document.getElementById('loginToCommentBtn');
            if (loginCommentBtn) {
                loginCommentBtn.addEventListener('click', () => {
                    showLoginPromptModal('Login Required', 'You must be logged in to post a comment.');
                });
            }
        });
    </script>
</body>

</html>