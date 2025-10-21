<?php
$unread_count = 0;
if (isset($_SESSION['user_id']) && isset($mysqli) && $mysqli->ping()) {
    $user_id = $_SESSION['user_id'];
    
    $allow_notifications = 0; // Default to false
    $sql_check_pref = "SELECT allow_notifications FROM users WHERE user_id = ?";
    if ($stmt_check = $mysqli->prepare($sql_check_pref)) {
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($row = $result->fetch_assoc()) {
            $allow_notifications = $row['allow_notifications'];
        }
        $stmt_check->close();
    }
    
    if ($allow_notifications) {
        $sql_count = "SELECT COUNT(*) as count FROM notifications WHERE recipient_user_id = ? AND is_read = 0";
        if ($stmt_count = $mysqli->prepare($sql_count)) {
            $stmt_count->bind_param("i", $user_id);
            $stmt_count->execute();
            if ($row = $stmt_count->get_result()->fetch_assoc()) {
                $unread_count = $row['count'];
            }
            $stmt_count->close();
        }
    }
}
?>
<nav class="custom-navbar navbar navbar-expand-md navbar-dark">
    <div class="container-fluid" style="max-width: 1000px;">
        <!-- Brand/Logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-house me-1"></i>
            BattleArt
        </a>

        <!-- Hamburger Button (Visible on mobile) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Content -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <!--LOGGED-IN USER LINKS-->
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="notification.php">
                            <i class="fa fa-bell me-1"></i>
                            Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-circle me-1"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!--GUEST USER LINKS-->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Sign In
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>
                            Sign Up
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

