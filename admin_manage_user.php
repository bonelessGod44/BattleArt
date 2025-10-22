<?php
require 'config.php'; // Defines $mysqli
require 'auth_check.php';
require 'helpers.php';

// Secure this page
$admin_user_id = requireAdmin();

// Get user ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}
$user_id = $_GET['id'];

// Handle Actions (Ban, Unban, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ban') {
        $stmt = $mysqli->prepare("UPDATE users SET account_status = 'banned' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } elseif ($action === 'unban') {
        $stmt = $mysqli->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } elseif ($action === 'delete' && $user_id != $admin_user_id) { // Prevent self-delete
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }

    if (isset($stmt)) {
        if (!$stmt->execute()) {
            die("Error processing action: " . $stmt->error);
        }
        $stmt->close();
    }

    if ($action === 'delete') {
        header("Location: admin_dashboard.php");
        exit;
    } else {
        header("Location: admin_manage_user.php?id=" . $user_id);
        exit;
    }
}

// Fetch user data
$user = null;
$stmt = $mysqli->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: admin_dashboard.php");
    exit;
}

// Helper function to get counts
function getCount($mysqli, $sql, $user_id)
{
    $count = 0;
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// Fetch stats
$user['artwork_count'] = getCount($mysqli, "SELECT COUNT(*) FROM challenges WHERE user_id = ?", $user_id);
$user['interpretation_count'] = getCount($mysqli, "SELECT COUNT(*) FROM interpretations WHERE user_id = ?", $user_id);
$user['comment_count'] = getCount($mysqli, "SELECT COUNT(*) FROM comments WHERE user_id = ?", $user_id);
$challenge_likes = getCount($mysqli, "SELECT COUNT(*) FROM likes WHERE user_id = ?", $user_id);
$interp_likes = getCount($mysqli, "SELECT COUNT(*) FROM interpretation_likes WHERE user_id = ?", $user_id);
$user['like_count'] = $challenge_likes + $interp_likes;

// Fetch recent activity
$activities = [];
$sql = "(SELECT 'artwork' as type, challenge_name as title, created_at as date FROM challenges WHERE user_id = ?)
        UNION
        (SELECT 'interpretation' as type, description as title, created_at as date FROM interpretations WHERE user_id = ?)
        UNION
        (SELECT 'comment' as type, comment_text as title, created_at as date FROM comments WHERE user_id = ?)
        ORDER BY date DESC
        LIMIT 10";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
        }

        body {
            background-color: var(--secondary-bg);
            padding-top: 30px;
        }

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

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-banned {
            background-color: #dc3545;
            color: white;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-banned {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>


<body>
    <?php require 'partials/navbar.php'; ?>
    </nav>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center mb-4">
                    <?php $avatar = !empty($user['user_profile_pic']) ? 'assets/uploads/' . htmlspecialchars($user['user_profile_pic']) : 'assets/images/blank-profile-picture.png'; ?>
                    <img src="<?php echo $avatar; ?>"
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
                    <div class="ms-auto mt-3 mt-md-0">
                        <?php if (($user['account_status'] ?? 'active') === 'active'): ?>
                            <form method="POST" class="d-inline" id="banForm">
                                <input type="hidden" name="action" value="ban">
                                <button type="button" id="banUserBtn" class="btn btn-warning me-2">
                                    <i class="bi bi-slash-circle"></i> Ban User
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline" id="unbanForm">
                                <input type="hidden" name="action" value="unban">
                                <button type="button" id="unbanUserBtn" class="btn btn-success me-2">
                                    <i class="bi bi-check-circle"></i> Unban User
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($user['user_id'] != $admin_user_id): ?>
                            <form method="POST" class="d-inline" id="deleteForm">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="button" id="deleteUserBtn" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Delete User
                                    </button>
                            </form>
                        <?php endif; ?>
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
                                                        echo $activity['type'] === 'artwork' ? 'palette' : ($activity['type'] === 'interpretation' ? 'brush' : 'chat-dots');
                                                        ?> me-2 fs-4" style="color: var(--primary-bg);"></i>
                                        <div>
                                            <strong><?php echo ucfirst($activity['type']); ?></strong>:
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
        <div id="message-container"></div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function showConfirmationModal(title, message, confirmText, confirmVariant, callback) {
                const container = document.getElementById('message-container');
                const modalId = 'confirmModal';
                // Remove any existing modal
                if (document.getElementById(modalId)) {
                    document.getElementById(modalId).remove();
                }

                container.innerHTML = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow-lg">
                            <div class="modal-header border-0 pb-0"><h5 class="modal-title text-${confirmVariant}">${title}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body pt-2 pb-4"><p class="text-muted">${message}</p></div>
                            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary rounded-pill flex-grow-1 me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" id="confirmActionBtn" class="btn btn-${confirmVariant} rounded-pill flex-grow-1">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                </div>`;

                const modalElement = document.getElementById(modalId);
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                document.getElementById('confirmActionBtn').onclick = () => {
                    modal.hide();
                    callback(); // Run the action
                };

                modalElement.addEventListener('hidden.bs.modal', () => {
                    modalElement.remove();
                });
            }
            document.addEventListener('DOMContentLoaded', () => {
                const banBtn = document.getElementById('banUserBtn');
                const unbanBtn = document.getElementById('unbanUserBtn');
                const deleteBtn = document.getElementById('deleteUserBtn');

                if (banBtn) {
                    banBtn.addEventListener('click', () => {
                        showConfirmationModal(
                            'Ban User',
                            'Are you sure you want to ban this user? They will not be able to log in.',
                            'Yes, Ban User',
                            'warning',
                            () => {
                                document.getElementById('banForm').submit();
                            }
                        );
                    });
                }

                if (unbanBtn) {
                    unbanBtn.addEventListener('click', () => {
                        showConfirmationModal(
                            'Unban User',
                            'Are you sure you want to unban this user? They will regain access to their account.',
                            'Yes, Unban User',
                            'success',
                            () => {
                                document.getElementById('unbanForm').submit();
                            }
                        );
                    });
                }

                if (deleteBtn) {
                    deleteBtn.addEventListener('click', () => {
                        showConfirmationModal(
                            'Delete User',
                            'PERMANENTLY delete this user? This will delete all their art, comments, and interpretations. This cannot be undone.',
                            'Delete Permanently',
                            'danger',
                            () => {
                                document.getElementById('deleteForm').submit();
                            }
                        );
                    });
                }
            });
        </script>
</body>

</html>
