<?php
require_once "admin_auth_check.php";
requireAdmin();

$user_id = $_SESSION['user_id'];

// Fetch admin data
$admin_data = [];
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM challenges) AS total_artworks,
        (SELECT COUNT(*) FROM users WHERE user_type = 'user') AS total_users,
        (SELECT COUNT(*) FROM comments) AS total_comments
        FROM users u
        WHERE u.user_id = ? AND u.user_type = 'admin'";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $admin_data = $row;
    $stmt->close();
}

// Fetch recent activity
$recent_activity = [];
$sql = "SELECT 'challenge' as type, challenge_name as name, created_at
        FROM challenges 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'comment' as type, comment_text as name, created_at
        FROM comments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC 
        LIMIT 10";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $recent_activity[] = $row;
    $result->free();        
}

// Placeholder paths
$bannerPicPath = 'assets/images/golem.png';
$profilePicPath = 'assets/images/golem.png';
$user_badge = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
<style>
:root {
    --primary-bg: #8c76ec;
    --secondary-bg: #a6e7ff;
    --light-purple: #c3b4fc;
    --text-dark: #333;
}

body {
    background: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
    font-family: 'Inter', sans-serif;
    color: var(--text-dark);
    margin: 0;
    padding: 0px 0 0 0;
}

/* Navbar */
.navbar-custom {
    background-color: var(--primary-bg);
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.navbar-brand-text { color: #fff; font-weight: bold; font-size: 1.25rem; }
.nav-link-custom { color: #fff !important; font-weight: 500; text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; transition: background-color 0.2s ease; }
.nav-link-custom:hover { background-color: rgba(255, 255, 255, 0.1); }
.dropdown-menu { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 0.5rem 0; }
.dropdown-item { padding: 0.5rem 1rem; border-radius: 6px; transition: background-color 0.2s; }
.dropdown-item:hover { background-color: var(--light-purple); color: #fff; }
.navbar-avatar { width: 32px; height: 32px; object-fit: cover; border-radius: 50%; border: 2px solid #fff; margin-right: 0.5rem; }

/* Profile container */
.profile-container {
    background-color: #fff;
    border-radius: 20px;
    padding: 2rem;
    margin: 2rem auto;
    max-width: 900px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.profile-banner { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 2rem; }
.profile-header { display: flex; align-items: center; border-bottom: 2px solid #ddd; padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
.profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2); margin-right: 1.5rem; }
.profile-info h3 { font-weight: bold; font-size: 1.5rem; margin-bottom: 0.25rem; color: var(--text-dark); }
.profile-info .badge { font-size: 0.75rem; font-weight: normal; background-color: var(--light-purple); color: #fff; padding: 0.25rem 0.5rem; border-radius: 10px; }
.profile-tabs .nav-link { color: #6c757d; font-weight: bold; padding: 0.75rem 1.5rem; border-bottom: 2px solid transparent; transition: color 0.2s, border-bottom 0.2s; }
.profile-tabs .nav-link:hover { color: var(--primary-bg); }
.profile-tabs .nav-link.active { color: var(--primary-bg); border-bottom-color: var(--primary-bg); }

/* Stats grid */
.stats-grid { display: flex; justify-content: space-between; padding: 1rem 0; margin-bottom: 2rem; }
.stat-item { text-align: center; flex-grow: 1; padding: 0.5rem; position: relative; }
.stat-item:not(:last-child):after { content: ''; position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 2px; height: 80%; background-color: #e9ecef; }
.stat-item h6 { font-size: 0.9rem; font-weight: bold; margin-bottom: 0.25rem; color: var(--text-dark); }
.stat-item p { font-size: 0.8rem; color: #777; margin: 0; }

/* Recent activity */
.log-item { display: flex; align-items: flex-start; margin-bottom: 1rem; }
.log-icon { width: 40px; text-align: center; font-size: 1.5rem; margin-right: 1rem; color: var(--primary-bg); }
.log-content p { margin: 0; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .profile-header { flex-direction: column; text-align: center; }
    .profile-info { margin-top: 1rem; }
    .stats-grid { flex-direction: column; }
    .stat-item:not(:last-child):after { display: none; }
    .stat-item { border-bottom: 2px solid #ddd; margin-bottom: 1rem; }
    .stat-item:last-child { border-bottom: none; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/home.png" alt="Home Icon" class="me-2" style="width: 24px; height: 24px;">
            <span class="navbar-brand-text">BattleArt</span>
        </a>
        <div class="d-flex align-items-center">
            <a href="admin_manage_art.php" class="nav-link nav-link-custom me-3"><i class="fas fa-image me-1"></i> Artworks</a>
            <a href="admin_manage_comments.php" class="nav-link nav-link-custom me-3"><i class="fas fa-comments me-1"></i> Comments</a>
            <div class="dropdown">
                <button class="btn btn-link nav-link-custom dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <img src="<?php echo $profilePicPath; ?>" class="navbar-avatar">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($admin_data['user_userName']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Profile container -->
<div class="profile-container">
    <div class="session-info mb-3">
        <i class="fas fa-check-circle me-2" style="color: #28a745;"></i>
        <strong>Welcome, <?php echo htmlspecialchars($admin_data['user_userName']); ?>!</strong>
        You are logged in as <strong><?php echo htmlspecialchars($admin_data['user_email']); ?></strong>
    </div>

    <img src="<?php echo $bannerPicPath; ?>" alt="Admin Banner" class="profile-banner">

    <!-- Tabs -->
    <ul class="nav nav-pills profile-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" id="profile-tab" href="#">Profile</a></li>
        <li class="nav-item"><a class="nav-link" id="stats-tab" href="#">Stats</a></li>
        <li class="nav-item"><a class="nav-link" id="activity-tab" href="#">Recent Activity</a></li>
    </ul>

    <!-- Profile Content -->
    <div id="profile-content">
        <div class="profile-header">
            <img src="<?php echo $profilePicPath; ?>" alt="Admin Avatar" class="profile-avatar">
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($admin_data['user_userName']); ?> <span class="badge"><?php echo $user_badge; ?></span></h3>
                <div class="profile-meta text-muted">
                    Email: <?php echo htmlspecialchars($admin_data['user_email']); ?><br>
                    Last Login: <?php echo $admin_data['last_login'] ?? 'N/A'; ?>
                </div>
            </div>
            <div class="profile-actions">
                <a href="edit-profile.php" class="btn btn-primary">Edit</a>
            </div>
        </div>
    </div>

    <!-- Stats Content -->
    <div id="stats-content" style="display:none;">
        <div class="stats-grid">
            <div class="stat-item">
                <i class="fas fa-images fa-2x mb-2"></i>
                <h6>Total Artworks</h6>
                <p><?php echo $admin_data['total_artworks']; ?> arts</p>
            </div>
            <div class="stat-item">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h6>Total Users</h6>
                <p><?php echo $admin_data['total_users']; ?> users</p>
            </div>
            <div class="stat-item">
                <i class="fas fa-comments fa-2x mb-2"></i>
                <h6>Total Comments</h6>
                <p><?php echo $admin_data['total_comments']; ?> comments</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity Content -->
    <div id="activity-content" style="display:none;">
        <h4>Recent Activity (Last 7 Days)</h4>
        <?php if(empty($recent_activity)): ?>
            <p class="text-muted">No recent activity found.</p>
        <?php else: ?>
            <?php foreach($recent_activity as $act): ?>
                <div class="log-item">
                    <div class="log-icon">
                        <i class="fas <?php echo $act['type'] === 'challenge' ? 'fa-plus-circle' : 'fa-comment'; ?>"></i>
                    </div>
                    <div class="log-content">
                        <p><?php echo $act['type'] === 'challenge' ? 'Challenge created: ' : 'Comment added: '; ?><strong><?php echo htmlspecialchars($act['name']); ?></strong></p>
                        <div class="log-date text-muted"><?php echo date('M j, Y, g:i a', strtotime($act['created_at'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.profile-tabs .nav-link');
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            e.target.classList.add('active');

            const contentIds = ['profile-content','stats-content','activity-content'];
            contentIds.forEach(id => document.getElementById(id).style.display = 'none');
            
            const targetId = e.target.id.replace('-tab','-content');
            document.getElementById(targetId).style.display = 'block';
        });
    });
});
</script>
</body>
</html>
<?php $mysqli->close(); ?>
