<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
date_default_timezone_set('Asia/Manila');
requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch all notifications for the logged-in user
$notifications = [];
// This SQL query is now valid because the target_parent_id column exists
$sql = "SELECT 
            n.*, 
            sender.user_userName AS sender_name,
            challenge.challenge_name
        FROM notifications n
        JOIN users sender ON n.sender_user_id = sender.user_id
        LEFT JOIN challenges challenge ON challenge.challenge_id = n.target_id OR challenge.challenge_id = n.target_parent_id
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
            padding-top: 80px;
        }

        .notification-item a {
            text-decoration: none;
            color: inherit;
        }

        .notification-item.unread {
            background-color: #f7f7ff;
        }

        .notification-item {
            position: relative;
        }

        .notification-item .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 10;
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
                        <div class="btn-group">
                            <button id="deleteAllBtn" type="button" class="btn btn-sm btn-outline-danger">Delete All</button>
                            <button id="markAllReadBtn" type="button" class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
                        </div>
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

                                // Determine the correct challenge ID for the link
                                $link_challenge_id = $notification['target_id']; // Default for old types
                                if ($notification['type'] == 'interpretation_like') {
                                    $link_challenge_id = $notification['target_parent_id'];
                                }

                                switch ($notification['type']) { // Switched to 'type'
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
                                        $text = "<strong>" . htmlspecialchars($notification['sender_name']) . "</strong> submitted an interpretation for your challenge: G" . htmlspecialchars($notification['challenge_name']) . "</strong>";
                                        break;

                                    // NEW CASE for interpretation likes
                                    case 'interpretation_like':
                                        $icon = 'fas fa-heart text-danger';
                                        $text = "<strong>" . htmlspecialchars($notification['sender_name']) . "</strong> liked your interpretation on the challenge: <strong>" . htmlspecialchars($notification['challenge_name']) . "</strong>";
                                        break;

                                    case 'challenge_update':
                                        $icon = 'fas fa-info-circle text-info';
                                        $text = "The challenge <strong>" . htmlspecialchars($notification['challenge_name']) . "</strong> has been updated by the author.";
                                        break;
                                }
                                ?>
                                <div class="list-group-item notification-item <?php echo $unreadClass; ?> p-3">
                                    <a href="mark_notifications_read.php?notification_id=<?php echo $notification['notification_id']; ?>&destination=<?php echo urlencode('challengepage.php?id=' . $link_challenge_id); ?>" class="d-flex align-items-center">
                                        <i class="<?php echo $icon; ?> fa-lg me-3" style="width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?php echo $text; ?></div>
                                            <small class="text-muted"><?php echo time_ago($notification['created_at']); ?></small>
                                        </div>
                                    </a>
                                    <button class="btn-close delete-notification-btn" data-id="<?php echo $notification['notification_id']; ?>" aria-label="Delete"></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form id="deleteForm" action="delete_notifications.php" method="post" style="display: none;">
                        <input type="hidden" name="action" id="deleteAction">
                        <input type="hidden" name="notification_id" id="deleteNotificationId">
                    </form>
                    <form id="markReadForm" action="mark_notifications_read.php" method="post" style="display: none;"></form>
                </div>
            </div>
        </div>
    </div>

    <div id="message-container"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showConfirmationModal(title, message, confirmText, confirmVariant, callback) {
            const container = document.getElementById('message-container');
            const modalId = 'confirmModal';
            if (document.getElementById(modalId)) document.getElementById(modalId).remove();
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
                callback();
            };
            modalElement.addEventListener('hidden.bs.modal', () => modalElement.remove());
        }

        document.addEventListener('DOMContentLoaded', () => {
            const deleteForm = document.getElementById('deleteForm');
            const deleteActionInput = document.getElementById('deleteAction');
            const deleteIdInput = document.getElementById('deleteNotificationId');

            // Handle "Mark All as Read" button
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', () => {
                    document.getElementById('markReadForm').submit();
                });
            }

            // Handle "Delete All" button
            const deleteAllBtn = document.getElementById('deleteAllBtn');
            if (deleteAllBtn) {
                deleteAllBtn.addEventListener('click', () => {
                    showConfirmationModal('Delete All Notifications', 'Are you sure you want to permanently delete all notifications?', 'Delete All', 'danger', () => {
                        deleteActionInput.value = 'all';
                        deleteForm.submit();
                    });
                });
            }

            // Handle individual delete buttons
            document.querySelectorAll('.delete-notification-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const notificationId = e.target.dataset.id;
                    showConfirmationModal('Delete Notification', 'Are you sure you want to delete this notification?', 'Delete', 'danger', () => {
                        deleteActionInput.value = 'single';
                        deleteIdInput.value = notificationId;
                        deleteForm.submit();
                    });
                });
            });
        });
    </script>
</body>

</html>