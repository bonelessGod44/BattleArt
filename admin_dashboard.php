<?php
require_once "admin_auth_check.php";
requireAdmin();

// Ensure session data is available
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch users data
$users = array();
$sql = "SELECT u.*,
        (SELECT MAX(login_time) FROM user_activity WHERE user_id = u.user_id) as last_seen,
        (SELECT login_time FROM user_activity WHERE user_id = u.user_id ORDER BY login_time DESC LIMIT 1) as joined_date
        FROM users u
        ORDER BY u.user_id DESC";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

// Helper function to format time ago
function timeAgo($time)
{
    $diff = time() - strtotime($time);

    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " mins ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } else {
        return floor($diff / 86400) . " days ago";
    }
}

require_once "config.php";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = $mysqli->query("SELECT user_profile_pic FROM users WHERE user_id = '$user_id'");

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profilePicPath = !empty($row['user_profile_pic'])
            ? 'uploads/' . $row['user_profile_pic']
            : 'assets/images/golem.png';
    } else {
        $profilePicPath = 'assets/images/golem.png';
    }
} else {
    $profilePicPath = 'assets/images/golem.png';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
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
            background-color: var(--secondary-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        .navbar-custom {
            background-color: var(--primary-bg);
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand-text {
            color: #fff;
            font-weight: bold;
            font-size: 1.25rem;
        }

        .nav-link-custom {
            color: #fff !important;
            font-weight: 500;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .nav-link-custom:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .dropdown-item:hover {
            background-color: var(--light-purple);
            color: #fff;
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }

        .navbar-brand:hover .navbar-brand-text {
            opacity: 0.9;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 3rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        h2 {
            color: var(--primary-bg);
            font-weight: bold;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2rem;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--light-purple);
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 1rem;
            text-align: center;
            vertical-align: middle;
        }

        .table tbody td {
            vertical-align: middle;
            padding: 1rem;
            text-align: center;
            border-color: #e9ecef;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .avatar-cell {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .avatar-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border: 2px solid var(--light-purple);
            transition: transform 0.2s ease;
        }

        .avatar-img:hover {
            transform: scale(1.1);
        }

        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .role-admin {
            background-color: #ff6b6b;
            color: #fff;
        }

        .role-user {
            background-color: #4ecdc4;
            color: #fff;
        }

        .btn-primary-custom {
            background-color: var(--primary-bg);
            border: none;
            color: #fff;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-custom:hover {
            background-color: var(--dark-purple-border);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(140, 118, 236, 0.3);
        }

        .last-seen {
            font-size: 0.9rem;
            color: #666;
        }

        .stats-container {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background: linear-gradient(135deg, var(--primary-bg), var(--dark-purple-border));
            color: #fff;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(140, 118, 236, 0.2);
        }

        .stat-card h4 {
            font-size: 2rem;
            margin: 0;
            font-weight: bold;
        }

        .stat-card p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin: 1.5rem;
                padding: 1.5rem;
            }

            .stats-container {
                flex-direction: column;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 0.75rem 0.5rem;
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
            <div class="d-flex align-items-center">
                <a href="admin_manage_art.php" class="nav-link nav-link-custom me-3">
                    <i class="bi bi-image me-1"></i> Artworks
                </a>
                <a href="admin_manage_comments.php" class="nav-link nav-link-custom me-3">
                    <i class="bi bi-chat-dots me-1"></i> Comments
                </a>
                <div class="dropdown">
                    <button class="btn btn-link nav-link-custom dropdown-toggle" type="button" id="adminDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($profilePicPath); ?>" alt="Admin Avatar"
                            class="rounded-circle me-1" style="width: 32px; height: 32px; object-fit: cover;">
                        <span class="d-none d-md-inline">
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                        </span>
                        <span
                            class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="admin_profile.php"><i
                                    class="bi bi-person me-2"></i>Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i
                                    class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <h2><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h4><?php echo count($users); ?></h4>
                <p><i class="bi bi-people-fill me-1"></i>Total Users</p>
            </div>
            <div class="stat-card">
                <h4><?php echo count(array_filter($users, function ($u) {
                    return $u['user_type'] === 'admin'; })); ?>
                </h4>
                <p><i class="bi bi-shield-fill-check me-1"></i>Admins</p>
            </div>
            <div class="stat-card">
                <h4><?php echo count(array_filter($users, function ($u) {
                    return $u['user_type'] === 'user'; })); ?></h4>
                <p><i class="bi bi-person-fill me-1"></i>Regular Users</p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th>Last Seen</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">No users found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="avatar-cell">
                                        <img src="<?php echo !empty($user['user_profile_pic'])
                                            ? 'uploads/' . htmlspecialchars($user['user_profile_pic'])
                                            : 'assets/images/default_avatar.png'; ?>" alt="Avatar" class="rounded-circle avatar-img">
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['user_userName']); ?></strong>
                                </td>
                                <td>
                                    <span
                                        class="role-badge <?php echo $user['user_type'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['joined_date'] ? date('M d, Y', strtotime($user['joined_date'])) : '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td>
                                    <span class="last-seen">
                                        <?php echo $user['last_seen'] ? timeAgo($user['last_seen']) : '<span class="text-muted">Never</span>'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="admin_manage_user.php?id=<?php echo $user['user_id']; ?>"
                                        class="btn btn-primary-custom">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>