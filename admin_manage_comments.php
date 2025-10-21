<?php
require_once "admin_auth_check.php";
requireAdmin();
require_once "config.php";
// Handle comment deletion
if (isset($_POST['delete_comment'])) {
    $comment_id = (int) $_POST['comment_id'];
    $sql = "DELETE FROM comments WHERE comment_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all comments with related information
$comments = [];
$sql = "SELECT c.*, u.user_userName, u.user_profile_pic, ch.challenge_name
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        JOIN challenges ch ON c.challenge_id = ch.challenge_id  
        ORDER BY c.created_at DESC";

if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments - BattleArt Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
        }

        .navbar-custom {
            background-color: var(--primary-bg);
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand-text {
            color: #fff;
            font-weight: bold;
        }

        .nav-link-custom {
            color: #fff !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .nav-link-custom:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 3rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        h2 {
            color: var(--primary-bg);
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .filter-section {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--light-purple);
            color: #fff;
            border: none;
            text-align: center;
            font-weight: 600;
        }

        .table tbody td {
            text-align: center;
            vertical-align: middle;
            color: var(--text-dark);
        }

        .table tbody tr:hover {
            background-color: #f9f9ff;
        }

        .btn-danger {
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .search-bar {
            width: 280px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin: 1.5rem;
                padding: 1.5rem;
            }

            .search-bar {
                width: 100%;
                margin-bottom: 1rem;
            }
        }

        .navbar img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="assets/images/home.png" alt="Home Icon" class="me-2" width="24" height="24">
                <span class="navbar-brand-text">BattleArt</span>
            </a>
            <div class="d-flex align-items-center">
                <a href="admin_manage_art.php" class="nav-link nav-link-custom me-3">
                    <i class="bi bi-image me-1"></i> Artworks
                </a>
                <a href="admin_manage_comments.php" class="nav-link nav-link-custom me-3 active">
                    <i class="bi bi-chat-dots me-1"></i> Comments
                </a>
                <div class="dropdown">
                    <button class="btn btn-link nav-link-custom dropdown-toggle" type="button" id="adminDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo !empty($_SESSION['profile_pic'])
                            ? 'uploads/' . htmlspecialchars($_SESSION['profile_pic'])
                            : 'assets/images/default_avatar.png'; ?>" alt="Admin Avatar" class="me-2">
                        <span
                            class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
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

    <!-- Main Container -->
    <div class="dashboard-container">
        <h2><i class="bi bi-chat-dots me-2"></i>Manage Comments</h2>

        <div class="filter-section">
            <input type="text" id="searchInput" class="form-control search-bar"
                placeholder="Search by user or artwork...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="commentTable">
                <thead>
                    <tr>
                        <th>Artwork</th>
                        <th>Username</th>
                        <th>Comment</th>
                        <th>Date Posted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i><br>
                                No comments found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($comment['challenge_name']); ?></td>
                                <td><?php echo htmlspecialchars($comment['user_userName']); ?></td>
                                <td><?php echo htmlspecialchars($comment['comment_text']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></td>
                                <td>
                                    <form method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <button type="submit" name="delete_comment" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('#commentTable tbody tr');
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            rows.forEach(row => {
                const artwork = row.cells[0].textContent.toLowerCase();
                const username = row.cells[1].textContent.toLowerCase();
                row.style.display = (artwork.includes(query) || username.includes(query)) ? '' : 'none';
            });
        });
    </script>
</body>

</html>