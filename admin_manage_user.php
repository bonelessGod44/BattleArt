<?php
require_once "admin_auth_check.php";
requireAdmin();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header("Location: admin_dashboard.php");
    exit;
}

// Handle user status updates if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'ban':
                $sql = "UPDATE users SET account_status = 'banned' WHERE user_id = ?";
                break;
            case 'unban':
                $sql = "UPDATE users SET account_status = 'active' WHERE user_id = ?";
                break;
            case 'delete':
                // Delete user's content first (you might want to keep some records)
                $sqls = [
                    "DELETE FROM comments WHERE user_id = ?",
                    "DELETE FROM likes WHERE user_id = ?",
                    "DELETE FROM interpretations WHERE user_id = ?",
                    "DELETE FROM challenges WHERE user_id = ?",
                    "DELETE FROM users WHERE user_id = ?"
                ];
                foreach ($sqls as $sql) {
                    if ($stmt = $mysqli->prepare($sql)) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                header("Location: admin_dashboard.php");
                exit;
        }
        
        if (isset($sql)) {
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Fetch user details
$user = null;
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM challenges WHERE user_id = u.user_id) as artwork_count,
        (SELECT COUNT(*) FROM interpretations WHERE user_id = u.user_id) as interpretation_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = u.user_id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE user_id = u.user_id) as like_count,
        (SELECT MAX(login_time) FROM user_activity WHERE user_id = u.user_id) as last_seen
        FROM users u 
        WHERE u.user_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    header("Location: admin_dashboard.php");
    exit;
}

// Fetch recent activity
$activities = array();
$sql = "SELECT 
            'artwork' as type,
            challenge_name as title,
            created_at as date,
            challenge_id as id
        FROM challenges 
        WHERE user_id = ?
        UNION ALL
        SELECT 
            'interpretation' as type,
            'Interpretation of artwork' as title,
            created_at as date,
            interpretation_id as id
        FROM interpretations 
        WHERE user_id = ?
        UNION ALL
        SELECT 
            'comment' as type,
            comment_text as title,
            created_at as date,
            comment_id as id
        FROM comments 
        WHERE user_id = ?
        ORDER BY date DESC 
        LIMIT 10";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    $stmt->close();
}

// Helper function from admin_dashboard.php
function timeAgo($time) {
    $diff = time() - strtotime($time);
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        return floor($diff/60) . " mins ago";
    } elseif ($diff < 86400) {
        return floor($diff/3600) . " hours ago";
    } else {
        return floor($diff/86400) . " days ago";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User - <?php echo htmlspecialchars($user['user_userName']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--light-purple);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: var(--primary-bg);
            margin-bottom: 0.5rem;
        }
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-active { background-color: #28a745; color: white; }
        .status-banned { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <i class="bi bi-arrow-left me-2"></i>
                <span class="navbar-brand-text">Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <img src="<?php echo htmlspecialchars($user['user_profile_pic'] ?? 'assets/images/default_avatar.png'); ?>" 
                         alt="User Avatar" 
                         class="rounded-circle me-3"
                         style="width: 100px; height: 100px; object-fit: cover;">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['user_userName']); ?></h2>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['user_email']); ?>
                        </p>
                        <span class="status-badge <?php echo ($user['account_status'] ?? 'active') === 'active' ? 'status-active' : 'status-banned'; ?>">
                            <?php echo ucfirst($user['account_status'] ?? 'active'); ?>
                        </span>
                    </div>
                    <div class="ms-auto">
                        <?php if (($user['account_status'] ?? 'active') === 'active'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to ban this user?');">
                                <input type="hidden" name="action" value="ban">
                                <button type="submit" class="btn btn-warning me-2">
                                    <i class="bi bi-slash-circle"></i> Ban User
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="unban">
                                <button type="submit" class="btn btn-success me-2">
                                    <i class="bi bi-check-circle"></i> Unban User
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Delete User
                            </button>
                        </form>
                    </div>
                </div>

                <div class="user-stats">
                    <div class="stat-card">
                        <h3><?php echo $user['artwork_count']; ?></h3>
                        <p class="mb-0">Artworks</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $user['interpretation_count']; ?></h3>
                        <p class="mb-0">Interpretations</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $user['comment_count']; ?></h3>
                        <p class="mb-0">Comments</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $user['like_count']; ?></h3>
                        <p class="mb-0">Likes Given</p>
                    </div>
                </div>

                <h4 class="mb-3">Recent Activity</h4>
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (empty($activities)): ?>
                            <p class="text-center py-4 text-muted">No recent activity</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-<?php 
                                            echo $activity['type'] === 'artwork' ? 'palette' : 
                                                ($activity['type'] === 'interpretation' ? 'brush' : 'chat-dots'); 
                                        ?> me-2"></i>
                                        <div>
                                            <strong><?php echo ucfirst($activity['type']); ?></strong>: 
                                            <?php echo htmlspecialchars(mb_strimwidth($activity['title'], 0, 100, "...")); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo timeAgo($activity['date']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>