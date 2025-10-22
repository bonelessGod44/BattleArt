<?php
require 'config.php'; // Defines $mysqli
require 'auth_check.php';
require 'helpers.php'; // Includes getAvatarPath() and timeAgo()

// Secure this page and get the admin's ID
$admin_user_id = requireAdmin();

// Fetch all users *except* the currently logged-in admin
$users = [];
$sql = "SELECT user_id, user_userName, user_profile_pic, user_type, joined_date, last_seen 
        FROM users 
        WHERE user_id != ?
        ORDER BY joined_date DESC";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $admin_user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        die("Error executing query: " . $stmt->error);
    }
    $stmt->close();
} else {
    die("Error preparing statement: " . $mysqli->error);
}

// Calculate stats
$total_users_result = $mysqli->query("SELECT COUNT(*) FROM users");
$total_users = $total_users_result->fetch_row()[0];
$total_users_result->free();

// Get count of *all* admins
$total_admins_result = $mysqli->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
$total_admins = $total_admins_result->fetch_row()[0];
$total_admins_result->free();

$total_regular_users = $total_users - $total_admins;

$authorAvatarPath = !empty($challenge['user_profile_pic'])
    ? 'assets/uploads/' . htmlspecialchars($challenge['user_profile_pic'])
    : 'assets/images/default-avatar.png';
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
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <?php require 'partials/navbar.php'; ?>

    <div class="dashboard-container">
        <h2><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h4><?php echo $total_users; ?></h4>
                <p><i class="bi bi-people-fill me-1"></i>Total Users</p>
            </div>
            <div class="stat-card">
                <h4><?php echo $total_admins; ?></h4>
                <p><i class="bi bi-shield-fill-check me-1"></i>Admins</p>
            </div>
            <div class="stat-card">
                <h4><?php echo $total_regular_users; ?></h4>
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
                                        <?php $avatar = !empty($user['user_profile_pic']) ? 'assets/uploads/' . htmlspecialchars(string: $user['user_profile_pic']) : 'assets/images/blank-profile-picture.png'; ?>
                                        <img src="<?php echo $avatar; ?>"
                                            alt="Avatar"
                                            class="rounded-circle avatar-img">
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['user_userName']); ?></strong>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo $user['user_type'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
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