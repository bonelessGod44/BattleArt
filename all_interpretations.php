<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if ($challenge_id === 0) die("Invalid Challenge ID.");

// Get the ID of the person viewing the page (will be null for guests)
$viewer_user_id = $_SESSION['user_id'] ?? null;

// Fetch challenge name for the header
$challenge = null;
$challenge_sql = "SELECT challenge_name FROM challenges WHERE challenge_id = ?";
if ($stmt_challenge = $mysqli->prepare($challenge_sql)) {
    $stmt_challenge->bind_param("i", $challenge_id);
    $stmt_challenge->execute();
    $challenge = $stmt_challenge->get_result()->fetch_assoc();
    $stmt_challenge->close();
}
if (!$challenge) die("Challenge not found.");

// Fetches interpretations, like counts, and if the current viewer has liked each one.
$interpretations = [];
$sql = "SELECT 
            i.*, 
            u.user_id,
            u.user_userName, 
            u.user_profile_pic,
            (SELECT COUNT(*) FROM interpretation_likes WHERE interpretation_id = i.interpretation_id) as like_count,
            (SELECT COUNT(*) FROM interpretation_likes WHERE interpretation_id = i.interpretation_id AND user_id = ?) as user_has_liked
        FROM interpretations i 
        JOIN users u ON i.user_id = u.user_id 
        WHERE i.challenge_id = ? 
        ORDER BY i.created_at DESC";

if ($stmt = $mysqli->prepare($sql)) {
    $viewer_id_for_query = $viewer_user_id ?? 0;
    $stmt->bind_param("ii", $viewer_id_for_query, $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $interpretations[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Interpretations for <?php echo htmlspecialchars($challenge['challenge_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
        }

        body {
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-top: 20px;
        }

        .card {
            border-radius: 20px;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .interpretation-card {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body class="font-sans">
    <?php require 'partials/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-5 bg-white p-4 rounded-3 shadow-sm mx-auto" style="max-width: 600px;">
            <h1 class="h3 fw-bold text-dark">All Interpretations</h1>
            <p class="text-muted mb-0">For the challenge: <strong style="color: var(--primary-bg);"><?php echo htmlspecialchars($challenge['challenge_name']); ?></strong></p>
        </header>

        <section>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php if (empty($interpretations)): ?>
                    <div class="col-12">
                        <div class="alert alert-light text-center">No interpretations have been submitted yet.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($interpretations as $interp): ?>
                        <?php $interpAvatarPath = !empty($interp['user_profile_pic']) ? 'assets/uploads/' . htmlspecialchars($interp['user_profile_pic']) : 'assets/images/default-avatar.png'; ?>
                        <div class="col">
                            <div class="card h-100 interpretation-card" data-interpretation-id="<?php echo $interp['interpretation_id']; ?>">
                                <div class="card-body d-flex flex-column">
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
                                        <img class="img-fluid rounded shadow-sm mb-3" src="assets/uploads/<?php echo htmlspecialchars($interp['art_filename']); ?>" alt="Interpretation">
                                    </a>
                                    <p class="card-text small fst-italic text-muted mt-auto">
                                        <?php if (!empty($interp['description'])) echo '"' . htmlspecialchars($interp['description']) . '"'; ?>
                                    </p>
                                    <div class="mt-3 pt-2 border-top text-muted small">
                                        <i class="fas fa-heart <?php echo ($interp['user_has_liked'] > 0) ? 'text-danger' : 'text-secondary'; ?>"></i>
                                        <span class="ms-1 card-like-count"><?php echo $interp['like_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <a href="challengepage.php?id=<?php echo $challenge_id; ?>" class="btn btn-light shadow position-fixed bottom-0 end-0 m-3">
            <i class="fas fa-arrow-left me-2"></i> Back to Challenge Page
        </a>
    </div>

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
                <div class="modal-footer" id="modalFooterActions"></div>
            </div>
        </div>
    </div>

    <div id="message-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function showLoginPromptModal(title, message) {
            /* Your existing modal function */ }

        const interpretationModal = document.getElementById('interpretationModal');
        interpretationModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const isUserLoggedIn = <?php echo json_encode(isset($viewer_user_id)); ?>;

            const interpretationId = button.getAttribute('data-interpretation-id');
            const imgSrc = button.getAttribute('data-img-src');
            const artistName = button.getAttribute('data-artist-name');
            const artistAvatar = button.getAttribute('data-artist-avatar');
            const artistId = button.getAttribute('data-artist-id');
            const description = button.getAttribute('data-description');
            let likeCount = parseInt(button.getAttribute('data-like-count'));
            let userHasLiked = button.getAttribute('data-user-has-liked') > 0;

            interpretationModal.querySelector('#modalImage').src = imgSrc;
            interpretationModal.querySelector('#modalArtistInfo').innerHTML = `<a href="public_profile.php?user_id=${artistId}" class="d-flex align-items-center text-decoration-none text-dark"><img src="${artistAvatar}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;"> <span class="fw-bold">${artistName}</span></a>`;
            interpretationModal.querySelector('#modalDescription').innerHTML = description ? `"${description}"` : '';

            const footer = interpretationModal.querySelector('#modalFooterActions');
            footer.innerHTML = `<a href="#" id="modalLikeBtn" class="btn btn-outline-danger"><i class="fas fa-heart ${userHasLiked ? 'text-danger' : ''}"></i> <span class="ms-1">${likeCount}</span></a>`;

            document.getElementById('modalLikeBtn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!isUserLoggedIn) {
                    showLoginPromptModal('Login Required', 'You must be logged in to like an interpretation.');
                    return;
                }

                const icon = this.querySelector('i');
                const countSpan = this.querySelector('span');
                const formData = new FormData();
                formData.append('interpretation_id', interpretationId);

                fetch('handle_interpretation_like.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            countSpan.textContent = data.likeCount;
                            userHasLiked = data.userHasLiked;

                            if (userHasLiked) icon.classList.add('text-danger');
                            else icon.classList.remove('text-danger');

                            const originalCard = document.querySelector(`.card[data-interpretation-id="${interpretationId}"]`);
                            if (originalCard) {
                                originalCard.querySelector('.card-like-count').textContent = data.likeCount;
                                const originalIcon = originalCard.querySelector('.fa-heart');
                                if (userHasLiked) {
                                    originalIcon.classList.remove('text-secondary');
                                    originalIcon.classList.add('text-danger');
                                } else {
                                    originalIcon.classList.remove('text-danger');
                                    originalIcon.classList.add('text-secondary');
                                }
                            }
                        }
                    });
            });
        });
    </script>
</body>

</html>