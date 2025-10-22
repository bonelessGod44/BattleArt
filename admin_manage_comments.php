<?php
require 'config.php'; // Defines $mysqli
require 'auth_check.php';

// Secure this page
$admin_user_id = requireAdmin();

// Handle Delete Action
if (isset($_POST['delete_comment']) && isset($_POST['comment_id'])) {
    $comment_id_to_delete = $_POST['comment_id'];

    $stmt = $mysqli->prepare("DELETE FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id_to_delete); // 'i' for integer
    if (!$stmt->execute()) {
        die("Error deleting comment: " . $stmt->error);
    }
    $stmt->close();

    header("Location: admin_manage_comments.php");
    exit;
}

// Fetch all comments
$comments = [];
$sql = "SELECT 
            cm.comment_id, 
            cm.comment_text, 
            cm.created_at, 
            u.user_userName, 
            c.challenge_name
        FROM 
            comments cm
        JOIN 
            users u ON cm.user_id = u.user_id
        JOIN 
            challenges c ON cm.challenge_id = c.challenge_id
        ORDER BY 
            cm.created_at DESC";

if ($result = $mysqli->query($sql)) {
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    die("Error fetching comments: " . $mysqli->error);
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
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 3rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
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
    <?php require 'partials/navbar.php'; ?>
    <div class="dashboard-container">
        <h2><i class="bi bi-chat-dots me-2"></i>Manage Comments</h2>
        <div class="filter-section">
            <input type="text" id="searchInput" class="form-control search-bar" placeholder="Search by user or artwork...">
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
                                <td style="text-align: left;"><?php echo htmlspecialchars($comment['comment_text']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></td>

                                <td>
                                    <form method="POST" id="deleteForm_<?php echo $comment['comment_id']; ?>">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <input type="hidden" name="delete_comment" value="1">
                                        <button type="button" class="btn btn-danger btn-sm delete-comment-btn"
                                            data-form-id="deleteForm_<?php echo $comment['comment_id']; ?>">
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
        <div id="message-container"></div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            /**
             * Displays a confirmation pop-up.
             */
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
                // Search Bar filter (Your existing code is correct)
                const searchInput = document.getElementById('searchInput');
                const table = document.getElementById('commentTable');
                const rows = table.querySelectorAll('tbody tr');

                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    rows.forEach(row => {
                        // Skip the 'No comments found' row
                        if (row.cells.length === 1 && row.querySelector('td[colspan="5"]')) return;

                        const artwork = row.cells[0].textContent.toLowerCase();
                        const username = row.cells[1].textContent.toLowerCase();
                        const commentText = row.cells[2].textContent.toLowerCase();
                        const isVisible = artwork.includes(query) ||
                            username.includes(query) ||
                            commentText.includes(query);
                        row.style.display = isVisible ? '' : 'none';
                    });
                });

                // CORRECTED: Event Listener for Delete Comment Buttons
                document.querySelectorAll('.delete-comment-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const formId = e.currentTarget.dataset.formId; // Get the form ID from the button
                        showConfirmationModal(
                            'Delete Comment',
                            'Are you sure you want to permanently delete this comment?',
                            'Delete',
                            'danger',
                            () => {
                                // Submit the specific form associated with the clicked button
                                document.getElementById(formId).submit();
                            }
                        );
                    });
                });
            });
        </script>
</body>


</html>