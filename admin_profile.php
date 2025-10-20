<?php
require_once "admin_auth_check.php";
requireAdmin();

$user_id = $_SESSION['user_id'];
$success_message = $error_message = '';

// Fetch admin user data
$admin_data = array();
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM challenges) as total_artworks,
        (SELECT COUNT(*) FROM users WHERE user_type = 'user') as total_users,
        (SELECT COUNT(*) FROM comments) as total_comments
        FROM users u 
        WHERE u.user_id = ? AND u.user_type = 'admin'";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin_data = $row;
    }
    $stmt->close();
}

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $new_email = trim($_POST['email']);
        $new_username = trim($_POST['username']);
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        
        // Verify current password
        if (!empty($current_password)) {
            $sql = "SELECT user_password FROM users WHERE user_id = ?";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($current_password, $row['user_password'])) {
                        // Update profile
                        $updates = array();
                        $types = "";
                        $params = array();

                        if (!empty($new_email)) {
                            $updates[] = "user_email = ?";
                            $types .= "s";
                            $params[] = $new_email;
                        }

                        if (!empty($new_username)) {
                            $updates[] = "user_userName = ?";
                            $types .= "s";
                            $params[] = $new_username;
                        }

                        if (!empty($new_password)) {
                            $updates[] = "user_password = ?";
                            $types .= "s";
                            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                        }

                        if (!empty($updates)) {
                            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
                            $types .= "i";
                            $params[] = $user_id;

                            if ($stmt = $mysqli->prepare($sql)) {
                                $stmt->bind_param($types, ...$params);
                                if ($stmt->execute()) {
                                    $success_message = "Profile updated successfully!";
                                } else {
                                    $error_message = "Error updating profile.";
                                }
                                $stmt->close();
                            }
                        }
                    } else {
                        $error_message = "Current password is incorrect.";
                    }
                }
                $stmt->close();
            }
        } else {
            $error_message = "Please enter your current password to make changes.";
        }
    }

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $user_id . '.' . $extension;
            $upload_path = 'assets/images/avatars/' . $new_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $sql = "UPDATE users SET avatar_url = ? WHERE user_id = ?";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("si", $new_filename, $user_id);
                    if ($stmt->execute()) {
                        $success_message = "Avatar updated successfully!";
                    } else {
                        $error_message = "Error updating avatar in database.";
                    }
                    $stmt->close();
                }
            } else {
                $error_message = "Error uploading avatar file.";
            }
        } else {
            $error_message = "Invalid file type or size. Please use JPG, PNG, or GIF under 5MB.";
        }
    }
}

// Fetch recent activity
$recent_activity = array();
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
    while ($row = $result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <!-- Keep your existing CSS -->
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="assets/images/home.png" alt="Home Icon" class="me-2" style="width: 24px; height: 24px;">
                <span class="navbar-brand-text">BattleArt Admin</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a href="logout.php" class="nav-link nav-link-custom">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <img id="banner-img" src="assets/images/night-road.png" alt="Admin Profile Banner" class="profile-banner">

        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($admin_data['user_profile_pic'] ?? 'assets/images/default_avatar.png'); ?>" 
                 alt="Admin Avatar" class="profile-avatar">
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($admin_data['user_userName']); ?></h3>
                <span class="badge bg-primary">Administrator</span>
                <p class="text-muted mt-2">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin_data['user_email']); ?>
                </p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Platform Statistics</h5>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4><?php echo $admin_data['total_users']; ?></h4>
                                <p>Total Users</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $admin_data['total_artworks']; ?></h4>
                                <p>Total Artworks</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $admin_data['total_comments']; ?></h4>
                                <p>Total Comments</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Activity</h5>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_activity as $activity): ?>
                                <li class="mb-2">
                                    <i class="fas fa-<?php echo $activity['type'] == 'challenge' ? 'palette' : 'comment'; ?>"></i>
                                    New <?php echo $activity['type']; ?>: 
                                    <?php echo htmlspecialchars(substr($activity['name'], 0, 30)) . (strlen($activity['name']) > 30 ? '...' : ''); ?>
                                    <small class="text-muted d-block">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Edit Profile</h5>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin_data['user_userName']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin_data['user_email']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Avatar</label>
                                <input type="file" name="avatar" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>