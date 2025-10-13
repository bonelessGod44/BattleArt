<?php
// Include authentication check
require_once 'auth_check.php';

// Require login to access this page
requireLogin();

// Get current user info
$user_info = getSessionInfo();
$user_email = getCurrentUser();

// Get username from database
require_once "config.php";

$user_id = $_SESSION['user_id'];
$user = []; // This will hold all the user's data

// This new query fetches the name, email, picture, bio, banner, and all privacy settings
$sql = "SELECT user_userName, user_email, user_profile_pic, user_bio, user_banner_pic, show_art, show_history, show_comments FROM users WHERE user_id = ?";

if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows == 1){
            $user = $result->fetch_assoc();
        } else {
            die("Error: User not found.");
        }
    }
    $stmt->close();
}
$mysqli->close();

// This line creates the correct path for the profile picture
$profilePicPath = !empty($user['user_profile_pic']) 
    ? 'assets/uploads/' . htmlspecialchars($user['user_profile_pic']) 
    : 'assets/images/default-avatar.png'; // Use a default image if none is set

$bannerPicPath = !empty($user['user_banner_pic']) 
    ? 'assets/uploads/' . htmlspecialchars($user['user_banner_pic']) 
    : 'assets/images/night-road.png'; // Fallback to your default banner

// Map email to user type for badge display
$user_types = [
    'admin@battleart.com' => 'Admin',
    'user@battleart.com' => 'User', 
    'artist@battleart.com' => 'Artist'
];
$user_badge = $user_types[$user_email] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
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
        }

        .navbar-custom {
            background-color: var(--primary-bg);
            padding: 1rem 1.5rem;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .navbar-brand-text {
            color: #fff;
            font-weight: bold;
        }

        .nav-link-custom {
            color: #fff;
            font-weight: 500;
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


        .log-item, .comment-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #ddd;
        }

            .log-item:last-child, .comment-item:last-child {
                border-bottom: none;
            }

        .log-icon, .comment-avatar {
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

        .log-content, .comment-content {
            flex-grow: 1;
        }

            .log-content p, .comment-text {
                margin: 0;
                line-height: 1.5;
            }

                .comment-text strong {
                    color: var(--primary-bg);
                }

        .log-date, .comment-date {
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
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/home.png" alt="Home Icon" class="me-2" style="width: 24px; height: 24px;">
                <span class="navbar-brand-text">BattleArt</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <img src="assets/images/hamburg.png" alt="Menu Icon" style="width: 24px; height: 24px;">
                </span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center me-2 me-lg-0" href="notification.html">
                            <i class="fas fa-inbox me-2"></i> Inbox
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center" href="profile.php">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center logout-btn" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <!-- Session Welcome Message -->
        <div class="session-info">
            <i class="fas fa-check-circle me-2" style="color: #28a745;"></i>
            <strong>Welcome back, <?php echo htmlspecialchars($user['user_userName']); ?>!</strong> 
            You are logged in as <strong><?php echo htmlspecialchars($user_email); ?></strong>
            <br><small>Logged in: <?php echo date('Y-m-d H:i:s', $user_info['login_time']); ?></small>
        </div>

        <img id="banner-img" src="<?php echo $bannerPicPath; ?>" alt="User Profile Banner" class="profile-banner">

        <ul class="nav nav-pills profile-tabs" id="profile-tabs">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" href="#">Profile</a>
            </li>
            <?php if ($user['show_art'] == 1): ?>
                <li class="nav-item">
                    <a class="nav-link" id="your-art-tab" href="#">Your art</a>
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
                    <div class="profile-meta text-muted">
                        Email: <?php echo htmlspecialchars($user['user_email']); ?><br>
                        Last Activity: <?php echo date('Y-m-d H:i:s', $user_info['last_activity']); ?><br>
                        Session Started: <?php echo date('M j, Y g:i:s A', $user_info['login_time']); ?><br>
                    </div>
                </div>
                <div class="profile-actions">
                    <div class="btn-group">
                        <a href="edit-profile.php" class="btn btn-profile">Edit</a>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-bullhorn fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Challenges declared</h6>
                    <p>— 1 challenges</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-paint-brush fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Arts Challenged</h6>
                    <p>— 3 arts</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-images fa-2x mb-2" style="color: var(--primary-bg);"></i>
                    <h6>Number of Art made</h6>
                    <p>— 4 arts</p>
                </div>
                <div class="stat-item goal-item">
                    <span class="goal-year">2025</span>
                    <span class="goal-progress">GOAL<br>20+</span>
                </div>
            </div>

        <div class="welcome-section">
            <h4>Welcome to <?php echo htmlspecialchars($user['user_userName']); ?>'s art battle profile!</h4>
            <p>
                <?php echo nl2br(htmlspecialchars($user['user_bio'])); ?>
            </p>
            <div class="star-rating">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
            </div>
        </div>
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