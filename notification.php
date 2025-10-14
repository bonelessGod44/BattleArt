<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
date_default_timezone_set('Asia/Manila');
requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch all notifications for the logged-in user
// We join multiple tables to get all the names and titles we need
$notifications = [];
$sql = "SELECT 
            n.*, 
            sender.user_userName AS sender_name,
            challenge.challenge_name
        FROM notifications n
        JOIN users sender ON n.sender_user_id = sender.user_id
        LEFT JOIN challenges challenge ON n.target_id = challenge.challenge_id
        WHERE n.recipient_user_id = ?
        ORDER BY n.created_at DESC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
$mysqli->close();

// A helper function to display time nicely
function time_ago($datetime)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - BattleArt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            background-image: linear-gradient(to bottom, #a6e7ff, #c3b4fc);
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-top: 20px;
        }

        .notification-item a {
            text-decoration: none;
            color: inherit;
        }

        .notification-item.unread {
            background-color: #f7f7ff;
        }
    </style>
</head>

<body>
    <?php include 'partials/navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card p-4 shadow-lg">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold text-dark mb-0">🔔 Notifications</h3>
                        <form action="mark_notifications_read.php" method="post">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
                        </form>
                    </div>

                    <div class="list-group list-group-flush">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center text-muted p-5">
                                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                                <p>You have no notifications yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                $icon = '';
                                $text = '';
                                $unreadClass = $notification['is_read'] == 0 ? 'unread' : '';

                                switch ($notification['type']) {
                                    case 'like':
                                        $icon = 'fas fa-heart text-danger';
                                        $text = "<strong>" . htmlspecialchars($notification['sender_name']) . "</strong> liked your challenge: <strong>" . htmlspecialchars($notification['challenge_name']) . "</strong>";
                                        break;
                                    case 'comment':
                                        $icon = 'fas fa-comment text-primary';
                                        $text = "<strong>" . htmlspecialchars($notification['sender_name']) . "</strong> commented on your challenge: <strong>" . htmlspecialchars($notification['challenge_name']) . "</strong>";
                                        break;
                                    case 'interpretation':
                                        $icon = 'fas fa-paint-brush text-success';
                                        $text = "<strong>" . htmlspecialchars($notification['sender_name']) . "</strong> submitted an interpretation for your challenge: <strong>" . htmlspecialchars($notification['challenge_name']) . "</strong>";
                                        break;
                                }
                                ?>
                                <div class="list-group-item notification-item <?php echo $unreadClass; ?>">
                                    <a href="challengepage.php?id=<?php echo $notification['target_id']; ?>" class="d-flex align-items-center">
                                        <i class="<?php echo $icon; ?> fa-lg me-3"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo $text; ?></div>
                                            <small class="text-muted"><?php echo time_ago($notification['created_at']); ?></small>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>