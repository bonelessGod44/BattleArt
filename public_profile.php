<?php
session_start();
// No login is required to view the page, so we remove auth checks here.
require_once "config.php";

// Get the user_id of the profile we want to VIEW from the URL.
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($profile_user_id === 0) {
    die("Error: No user specified.");
}

// We still need to know who is VIEWING the page (they could be a guest or logged in).
$viewer_user_id = $_SESSION['user_id'] ?? null;
$user = []; // This will hold all the user's data

//Fetch main user info
$sql_user = "SELECT user_userName, user_email, user_profile_pic, user_bio, user_banner_pic, show_art, show_history, show_comments, user_type, account_status FROM users WHERE user_id = ?";
if ($stmt_user = $mysqli->prepare($sql_user)) {
    $stmt_user->bind_param("i", $profile_user_id);
    if ($stmt_user->execute()) {
        $user = $stmt_user->get_result()->fetch_assoc();
        if (!$user) {
            die("Error: User not found.");
        }
    }
    $stmt_user->close();
}

//Fetch stats for the grid
//Challenges Declared (Original art by user)
$challenges_declared_count = 0;
$sql_challenges = "SELECT COUNT(*) as count FROM challenges WHERE user_id = ?";
if ($stmt_challenges = $mysqli->prepare($sql_challenges)) {
    $stmt_challenges->bind_param("i", $profile_user_id);
    $stmt_challenges->execute();
    $challenges_declared_count = $stmt_challenges->get_result()->fetch_assoc()['count'];
    $stmt_challenges->close();
}

//Arts Challenged (Interpretations by user)
$arts_challenged_count = 0;
$sql_interpretations = "SELECT COUNT(*) as count FROM interpretations WHERE user_id = ?";
if ($stmt_interpretations = $mysqli->prepare($sql_interpretations)) {
    $stmt_interpretations->bind_param("i", $profile_user_id);
    $stmt_interpretations->execute();
    $arts_challenged_count = $stmt_interpretations->get_result()->fetch_assoc()['count'];
    $stmt_interpretations->close();
}
$total_art_made = $challenges_declared_count + $arts_challenged_count;

//Fetch content for "Your Art" tab
$user_challenges = [];
$sql_user_challenges = "SELECT challenge_id, challenge_name, original_art_filename FROM challenges WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt_user_challenges = $mysqli->prepare($sql_user_challenges)) {
    $stmt_user_challenges->bind_param("i", $profile_user_id);
    $stmt_user_challenges->execute();
    $result = $stmt_user_challenges->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_challenges[] = $row;
    }
    $stmt_user_challenges->close();
}

//Fetch content for "History" tab (User's recent comments)
$user_history = [];
$sql_history = "(SELECT
                    'created_challenge' as event_type,
                    challenge_name as event_title,
                    NULL as event_content,
                    challenge_id,
                    created_at as event_date
                FROM challenges
                WHERE user_id = ?)
                UNION
                (SELECT
                    'posted_comment' as event_type,
                    ch.challenge_name as event_title,
                    co.comment_text as event_content,
                    co.challenge_id,
                    co.created_at as event_date
                FROM comments co
                JOIN challenges ch ON co.challenge_id = ch.challenge_id
                WHERE co.user_id = ?)
                UNION
                (SELECT
                    'created_interpretation' as event_type,
                    ch.challenge_name as event_title,
                    i.description as event_content,
                    i.challenge_id,
                    i.created_at as event_date
                FROM interpretations i
                JOIN challenges ch ON i.challenge_id = ch.challenge_id
                WHERE i.user_id = ?)
                UNION
                (SELECT
                    'liked_challenge' as event_type,
                    ch.challenge_name as event_title,
                    NULL as event_content,
                    l.challenge_id,
                    l.created_at as event_date
                FROM likes l
                JOIN challenges ch ON l.challenge_id = ch.challenge_id
                WHERE l.user_id = ?)
                UNION
                (SELECT
                    'liked_interpretation' as event_type,
                    u.user_userName as event_title,
                    ch.challenge_name as event_content,
                    ch.challenge_id,
                    il.created_at as event_date
                FROM interpretation_likes il
                JOIN interpretations i ON il.interpretation_id = i.interpretation_id
                JOIN users u ON i.user_id = u.user_id
                JOIN challenges ch ON i.challenge_id = ch.challenge_id
                WHERE il.user_id = ?)
                ORDER BY event_date DESC
                LIMIT 10";

if ($stmt_history = $mysqli->prepare($sql_history)) {
    // We now need to bind the user_id five times
    $stmt_history->bind_param("iiiii", $profile_user_id, $profile_user_id, $profile_user_id, $profile_user_id, $profile_user_id);
    $stmt_history->execute();
    $result = $stmt_history->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_history[] = $row;
    }
    $stmt_history->close();
}

//Fetch content for "Comments" tab (Comments on the user's art)
$comments_on_art = [];
$sql_comments_on_art = "SELECT co.*, u.user_userName, u.user_profile_pic, ch.challenge_name
                        FROM comments co
                        JOIN users u ON co.user_id = u.user_id
                        JOIN challenges ch ON co.challenge_id = ch.challenge_id
                        WHERE ch.user_id = ? AND co.user_id != ?
                        ORDER BY co.created_at DESC LIMIT 10";
if ($stmt_comments_on_art = $mysqli->prepare($sql_comments_on_art)) {
    $stmt_comments_on_art->bind_param("ii", $profile_user_id, $profile_user_id);
    $stmt_comments_on_art->execute();
    $result = $stmt_comments_on_art->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments_on_art[] = $row;
    }
    $stmt_comments_on_art->close();
}

//Rating block
$avg_rating = 0.0;
$rating_count = 0;
$sql_rating = "SELECT AVG(rating_value) as avg_rating, COUNT(*) as rating_count FROM ratings WHERE rated_user_id = ?";
if ($stmt_rating = $mysqli->prepare($sql_rating)) {
    $stmt_rating->bind_param("i", $profile_user_id);
    $stmt_rating->execute();
    $result = $stmt_rating->get_result()->fetch_assoc();
    if ($result && $result['rating_count'] > 0) {
        $avg_rating = round($result['avg_rating'], 1);
        $rating_count = $result['rating_count'];
    }
    $stmt_rating->close();
}


function generate_stars($rating)
{
    $rating = round($rating * 2) / 2; // Round to the nearest half (e.g., 3.7 -> 3.5, 3.8 -> 4.0)
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            echo '<i class="fas fa-star"></i>'; // Full star
        } elseif ($rating > ($i - 1)) {
            echo '<i class="fas fa-star-half-alt"></i>'; // Half star
        } else {
            echo '<i class="far fa-star"></i>'; // Empty star
        }
    }
}

// This line creates the correct path for the profile picture
$profilePicPath = !empty($user['user_profile_pic'])
    ? 'assets/uploads/' . htmlspecialchars($user['user_profile_pic'])
    : 'assets/images/blank-profile-picture.png'; // Use a default image if none is set

$bannerPicPath = !empty($user['user_banner_pic'])
    ? 'assets/uploads/' . htmlspecialchars($user['user_banner_pic'])
    : 'assets/images/night-road.png'; // Fallback to your default banner

// Map email to user type for badge display
$user_types = [
    'admin@battleart.com' => 'Admin',
    'user@battleart.com' => 'User',
    'artist@battleart.com' => 'Artist'
];

$user_badge = ucfirst(htmlspecialchars($user['user_type'] ?? 'User')); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
            --dark-purple-border: #7b68ee;
            --text-dark: #333;
        }

        body {
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
            color: #fff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            padding-top: 20px;
        }

        .profile-container {
            background-color: #fff;
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 900px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: var(--text-dark);
        }

        .profile-banner {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #ddd;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            margin-right: 1.5rem;
        }

        .profile-info h3 {
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .profile-info .badge {
            font-size: 0.75rem;
            font-weight: normal;
            background-color: var(--light-purple);
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
        }

        .profile-info .text-muted {
            font-size: 0.9rem;
            color: #777 !important;
            margin-top: 0.5rem;
        }

        .profile-actions {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .profile-actions .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn-profile {
            background-color: var(--light-purple);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            transition: background-color 0.2s ease;
        }

        .btn-profile:hover {
            background-color: var(--primary-bg);
            color: #fff;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }


        .profile-tabs .nav-link {
            color: #6c757d;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
        }

        .profile-tabs .nav-link:hover {
            color: var(--primary-bg);
        }

        .profile-tabs .nav-link.active {
            color: var(--primary-bg);
            border-bottom-color: var(--primary-bg);
        }


        .stats-grid {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
            flex-grow: 1;
            padding: 0.5rem;
            position: relative;
        }

        .stat-item:not(:last-child):after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 2px;
            height: 80%;
            background-color: #e9ecef;
        }

        .stat-item h6 {
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .stat-item p {
            font-size: 0.8rem;
            color: #777;
            margin: 0;
        }


        .goal-item {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .goal-item .goal-year {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-bg);
        }

        .goal-item .goal-progress {
            font-size: 0.8rem;
            color: #777;
        }


        .welcome-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .welcome-section h4 {
            font-weight: 300;
            font-size: 1.5rem;
            color: var(--light-purple);
            font-style: italic;
        }

        .welcome-section h5 {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }


        .star-rating {
            font-size: 1.5rem;
            color: #ffda6a;
            display: inline-block;
        }


        .prioritizing-section {
            text-align: center;
            border-top: 2px solid #ddd;
            padding-top: 2rem;
        }

        .prioritizing-section h5 {
            color: var(--text-dark);
            font-weight: bold;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        /* Session info alert */
        .session-info {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s ease;
        }

        .logout-btn:hover {
            background-color: #c82333;
            color: white;
        }


        .art-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .art-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #e9ecef;
        }

        .art-card-body {
            padding: 1rem;
            color: var(--text-dark);
        }

        .art-card-body h6 {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .art-card-body p {
            font-size: 0.85rem;
            color: #777;
            margin: 0;
        }

        .art-card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
        }

        .art-card-actions .icon-link {
            color: #6c757d;
            font-size: 1rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .art-card-actions .icon-link:hover {
            color: var(--primary-bg);
        }


        .log-item,
        .comment-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #ddd;
        }

        .log-item:last-child,
        .comment-item:last-child {
            border-bottom: none;
        }

        .log-icon,
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-purple);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            margin-right: 1rem;
        }

        .comment-avatar {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .comment-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .log-content,
        .comment-content {
            flex-grow: 1;
        }

        .log-content p,
        .comment-text {
            margin: 0;
            line-height: 1.5;
        }

        .comment-text strong {
            color: var(--primary-bg);
        }

        .log-date,
        .comment-date {
            font-size: 0.8rem;
            color: #777;
            margin-top: 0.25rem;
        }



        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-info {
                margin-top: 1rem;
            }

            .profile-actions {
                margin-top: 1rem;
                margin-left: 0;
            }

            .stats-grid {
                flex-direction: column;
            }

            .stat-item:not(:last-child):after {
                display: none;
            }

            .stat-item {
                border-bottom: 2px solid #ddd;
                margin-bottom: 1rem;
            }

            .stat-item:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body>
    <?php require 'partials/navbar.php'; ?>

    <div class="profile-container">

        <img id="banner-img" src="<?php echo $bannerPicPath; ?>" alt="User Profile Banner" class="profile-banner">

        <ul class="nav nav-pills profile-tabs" id="profile-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" href="#">Profile</a>
            </li>
            <?php if ($user['show_art'] == 1): ?>
                <li class="nav-item">
                    <a class="nav-link" id="your-art-tab" href="#">Their Art</a>
                </li>
            <?php endif; ?>
            <?php if ($user['show_history'] == 1): ?>
                <li class="nav-item">
                    <a class="nav-link" id="history-tab" href="#">History</a>
                </li>
            <?php endif; ?>
            <?php if ($user['show_comments'] == 1): ?>
                <li class="nav-item">
                    <a class="nav-link" id="comments-tab" href="#">Comments</a>
                </li>
            <?php endif; ?>
        </ul>

        <div id="profile-content">
            <div class="profile-header">
                <div class="position-relative">
                    <img id="avatar-img" src="<?php echo $profilePicPath; ?>" alt="User Avatar" class="profile-avatar">
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($user['user_userName']); ?> <span class="badge"><?php echo htmlspecialchars($user_badge); ?></span></h3>
                    <?php if (($user['account_status'] ?? 'active') === 'banned'): ?>
                        <span class="badge bg-danger ms-2">Banned</span>
                        <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-bullhorn fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Challenges declared</h6>
                    <p>— <?php echo $challenges_declared_count; ?> challenges</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-paint-brush fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Arts Challenged</h6>
                    <p>— <?php echo $arts_challenged_count; ?> arts</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-images fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Number of Art made</h6>
                    <p>— <?php echo $total_art_made; ?> arts</p>
                </div>
            </div>

            <div class="welcome-section">
                <h4>Welcome to <?php echo htmlspecialchars($user['user_userName']); ?>'s art battle profile!</h4>
                <p>
                    <?php echo nl2br(htmlspecialchars($user['user_bio'])); ?>
                </p>
                <div class="star-rating">
                    <?php generate_stars($avg_rating); ?>
                </div>
                <?php if ($rating_count > 0): ?>
                    <p class="text-muted mt-2">
                        <strong><?php echo $avg_rating; ?></strong> average rating from <strong><?php echo $rating_count; ?></strong> user(s).
                    </p>
                <?php else: ?>
                    <p class="text-muted mt-2">This user has no ratings yet.</p>
                <?php endif; ?>
            </div>

        </div>
        <div id="your-art-content" style="display: none;">
            <h2 class="mb-4 h4 fw-bold"><?php echo htmlspecialchars($user['user_userName']); ?>'s Original Challenges</h2>
            <div class="row">
                <?php if (empty($user_challenges)): ?>
                    <p class="text-muted"><?php echo htmlspecialchars($user['user_userName']); ?> hasn't created any challenges yet.</p>
                <?php else: ?>
                    <?php foreach ($user_challenges as $art): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card art-card h-100">
                                <img src="assets/uploads/<?php echo htmlspecialchars($art['original_art_filename']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($art['challenge_name']); ?>" style="height: 150px; object-fit: cover;">
                                <div class="card-body art-card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo htmlspecialchars($art['challenge_name']); ?></h6>
                                    <a href="challengepage.php?id=<?php echo $art['challenge_id']; ?>" class="btn btn-sm btn-outline-primary mt-auto">View Challenge</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="history-content" style="display: none;">
            <h2 class="mb-4 h4 fw-bold"><?php echo htmlspecialchars($user['user_userName']); ?>'s Recent Activity</h2>
            <?php if (empty($user_history)): ?>
                <p class="text-muted"><?php echo htmlspecialchars($user['user_userName']); ?> has no recent activity.</p>
            <?php else: ?>
                <?php foreach ($user_history as $event): ?>
                    <div class="log-item">
                        <?php if ($event['event_type'] === 'created_challenge'): ?>
                            <div class="log-icon"><i class="fas fa-plus-circle"></i></div>
                            <div class="log-content">
                                <p><?php echo htmlspecialchars($user['user_userName']); ?> created the challenge <a href="challengepage.php?id=<?php echo $event['challenge_id']; ?>"><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></a>.</p>
                                <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($event['event_date'])); ?></div>
                            </div>
                        <?php elseif ($event['event_type'] === 'posted_comment'): ?>
                            <div class="log-icon"><i class="fas fa-comment"></i></div>
                            <div class="log-content">
                                <p><?php echo htmlspecialchars($user['user_userName']); ?> commented on <a href="challengepage.php?id=<?php echo $event['challenge_id']; ?>"><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></a>: "<?php echo htmlspecialchars($event['event_content']); ?>"</p>
                                <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($event['event_date'])); ?></div>
                            </div>
                        <?php elseif ($event['event_type'] === 'created_interpretation'):
                            $interp_desc = !empty($event['event_content']) ? ': "' . htmlspecialchars(substr($event['event_content'], 0, 50)) . '..."' : '.';
                        ?>
                            <div class="log-icon"><i class="fas fa-paint-brush"></i></div>
                            <div class="log-content">
                                <p><?php echo htmlspecialchars($user['user_userName']); ?> submitted an interpretation on <a href="challengepage.php?id=<?php echo $event['challenge_id']; ?>"><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></a><?php echo $interp_desc; ?></p>
                                <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($event['event_date'])); ?></div>
                            </div>
                        <?php elseif ($event['event_type'] === 'liked_challenge'): ?>
                            <div class="log-icon text-danger"><i class="fas fa-heart"></i></div>
                            <div class="log-content">
                                <p><?php echo htmlspecialchars($user['user_userName']); ?> liked the challenge <a href="challengepage.php?id=<?php echo $event['challenge_id']; ?>"><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></a>.</p>
                                <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($event['event_date'])); ?></div>
                            </div>
                        <?php elseif ($event['event_type'] === 'liked_interpretation'): ?>
                            <div class="log-icon text-danger"><i class="fas fa-heart"></i></div>
                            <div class="log-content">
                                <p><?php echo htmlspecialchars($user['user_userName']); ?> liked <strong><?php echo htmlspecialchars($event['event_title']); ?>'s</strong> interpretation on <a href="challengepage.php?id=<?php echo $event['challenge_id']; ?>"><strong><?php echo htmlspecialchars($event['event_content']); ?></strong></a>.</p>
                                <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($event['event_date'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="comments-content" style="display: none;">
            <h2 class="mb-4 h4 fw-bold">Recent Comments on <?php echo htmlspecialchars($user['user_userName']); ?>'s Art</h2>
            <?php if (empty($comments_on_art)): ?>
                <p class="text-muted">No one has commented on <?php echo htmlspecialchars($user['user_userName']); ?>'s challenges yet.</p>
            <?php else: ?>
                <?php foreach ($comments_on_art as $comment): ?>
                    <?php $avatar = !empty($comment['user_profile_pic']) ? 'assets/uploads/' . htmlspecialchars($comment['user_profile_pic']) : 'assets/images/blank-profile-picture.png'; ?>
                    <div class="comment-item">
                        <div class="comment-avatar"><img src="<?php echo $avatar; ?>" alt=""></div>
                        <div class="comment-content">
                            <p class="comment-text"><strong><?php echo htmlspecialchars($comment['user_userName']); ?></strong> commented on <a href="challengepage.php?id=<?php echo $comment['challenge_id']; ?>"><strong><?php echo htmlspecialchars($comment['challenge_name']); ?></strong></a>: "<?php echo htmlspecialchars($comment['comment_text']); ?>"</p>
                            <div class="comment-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($comment['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.profile-tabs .nav-link');
            const contentSections = document.querySelectorAll('.profile-container > div');

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    tabs.forEach(t => t.classList.remove('active'));
                    e.target.classList.add('active');
                    const contentId = e.target.id.replace('-tab', '-content');
                    contentSections.forEach(section => {
                        if (section.id && ['profile-content', 'your-art-content', 'history-content', 'comments-content'].includes(section.id)) {
                            section.style.display = 'none';
                        }
                    });
                    const activeContent = document.getElementById(contentId);
                    if (activeContent) {
                        activeContent.style.display = 'block';
                    }
                });
            });
        });
    </script>
</body>

</html>