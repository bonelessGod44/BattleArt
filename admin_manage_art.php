<?php
require 'config.php'; // Defines $mysqli
require 'auth_check.php';

// Secure this page
$admin_user_id = requireAdmin();

// Handle Delete Action
if (isset($_POST['delete_art']) && isset($_POST['art_id'])) {
    $art_id_to_delete = $_POST['art_id'];

    // Note: The database schema has ON DELETE CASCADE.
    // Deleting a challenge will auto-delete related comments, interpretations, etc.
    $stmt = $mysqli->prepare("DELETE FROM challenges WHERE challenge_id = ?");
    $stmt->bind_param("i", $art_id_to_delete); // 'i' for integer
    if (!$stmt->execute()) {
        die("Error deleting artwork: " . $stmt->error);
    }
    $stmt->close();

    header("Location: admin_manage_art.php");
    exit;
}

// Fetch all artworks
$artworks = [];
$sql = "SELECT 
            c.challenge_id, 
            c.challenge_name, 
            c.category, 
            c.original_art_filename, 
            c.created_at, 
            u.user_userName, 
            u.user_profile_pic,
            (SELECT COUNT(*) FROM likes l WHERE l.challenge_id = c.challenge_id) AS like_count,
            (SELECT COUNT(*) FROM interpretations i WHERE i.challenge_id = c.challenge_id) AS interpretation_count
        FROM 
            challenges c
        JOIN 
            users u ON c.user_id = u.user_id
        ORDER BY 
            c.created_at DESC";

if ($result = $mysqli->query($sql)) {
    $artworks = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    die("Error fetching artworks: " . $mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Artworks</title>
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
            max-width: 1400px;
            margin: 3rem auto;
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        h2 {
            color: var(--primary-bg);
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .btn-toggle {
            background: #fff;
            border: 2px solid var(--light-purple);
            color: var(--primary-bg);
            padding: 0.5rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-toggle:hover {
            background: var(--light-purple);
            color: #fff;
        }

        .btn-toggle.active {
            background: var(--primary-bg);
            color: #fff;
        }

        .art-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .art-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(140, 118, 236, 0.2);
        }

        .art-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .art-card .card-body {
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .art-card .card-title {
            color: var(--primary-bg);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .artist-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .artist-avatar {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }

        .stats-badge {
            display: inline-flex;
            align-items: center;
            background: #f0f0f0;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .btn-danger-custom {
            background-color: #ff6b6b;
            border: none;
            color: #fff;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .btn-danger-custom:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3);
        }

        @media (max-width:768px) {
            .dashboard-container {
                margin: 1.5rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php require 'partials/navbar.php'; ?>

    <div class="dashboard-container">
        <h2><i class="bi bi-images me-2"></i>Manage Artworks</h2>

        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="searchInput" placeholder="🔍 Search artwork or artist...">
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="Digital Painting">Digital Painting</option>
                                <option value="Sci-Fi">Sci-Fi</option>
                                <option value="Fantasy">Fantasy</option>
                                <option value="Abstract">Abstract</option>
                                <option value="Portraits">Portraits</option>
                                <option value="Landscapes">Landscapes</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-toggle active" id="cardViewBtn"><i class="bi bi-grid-3x3-gap me-1"></i> Cards</button>
                        <button class="btn btn-toggle" id="tableViewBtn"><i class="bi bi-list-ul me-1"></i> Table</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="cardView" class="row g-4">
            <?php if (empty($artworks)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-image" style="font-size:4rem;color:#ccc;"></i>
                    <p class="text-muted mt-3">No artworks found</p>
                </div>
            <?php else: ?>
                <?php foreach ($artworks as $art): ?>
                    <div class="col-lg-4 col-md-6 artwork-item" data-category="<?php echo htmlspecialchars($art['category'] ?? ''); ?>"
                        data-title="<?php echo htmlspecialchars(strtolower($art['challenge_name'])); ?>"
                        data-artist="<?php echo htmlspecialchars(strtolower($art['user_userName'])); ?>">
                        <div class="card art-card">
                            <img src="assets/uploads/<?php echo htmlspecialchars($art['original_art_filename']); ?>" class="card-img-top" alt="Artwork">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($art['challenge_name']); ?></h5>
                                <div class="artist-info">
                                    <span class="text-muted"><?php echo htmlspecialchars($art['user_userName']); ?></span>
                                </div>
                                <div class="d-flex justify-content-start align-items-center mb-3">
                                    <span class="stats-badge"><i class="bi bi-heart-fill text-danger"></i> <?php echo $art['like_count']; ?></span>
                                    <span class="stats-badge"><i class="bi bi-palette-fill text-primary"></i> <?php echo $art['interpretation_count']; ?></span>
                                </div>
                                <form method="POST" id="deleteFormCard_<?php echo $art['challenge_id']; ?>">
                                    <input type="hidden" name="art_id" value="<?php echo $art['challenge_id']; ?>">
                                    <input type="hidden" name="delete_art" value="1">
                                    <button type="button" class="btn btn-danger-custom w-100 delete-art-btn"
                                        data-form-id="deleteFormCard_<?php echo $art['challenge_id']; ?>">
                                        <i class="bi bi-trash me-1"></i> Delete Artwork
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="tableView" class="table-responsive" style="display:none;">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Category</th>
                        <th>Likes</th>
                        <th>Interpretations</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($artworks)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No artworks found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($artworks as $art): ?>
                            <tr class="artwork-item" data-category="<?php echo htmlspecialchars($art['category'] ?? ''); ?>"
                                data-title="<?php echo htmlspecialchars(strtolower($art['challenge_name'])); ?>"
                                data-artist="<?php echo htmlspecialchars(strtolower($art['user_userName'])); ?>">
                                <td><img src="assets/uploads/<?php echo htmlspecialchars($art['original_art_filename']); ?>" class="rounded" width="80" height="80" style="object-fit:cover;"></td>
                                <td><strong><?php echo htmlspecialchars($art['challenge_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($art['user_userName']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($art['category'] ?? 'N/A'); ?></span></td>
                                <td><i class="bi bi-heart-fill text-danger"></i> <?php echo $art['like_count']; ?></td>
                                <td><i class="bi bi-palette-fill text-primary"></i> <?php echo $art['interpretation_count']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($art['created_at'])); ?></td>
                                <td>
                                    <form method="POST" id="deleteFormTable_<?php echo $art['challenge_id']; ?>">
                                        <input type="hidden" name="art_id" value="<?php echo $art['challenge_id']; ?>">
                                        <input type="hidden" name="delete_art" value="1">
                                        <button type="button" class="btn btn-danger-custom btn-sm delete-art-btn"
                                            data-form-id="deleteFormTable_<?php echo $art['challenge_id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
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
                document.querySelectorAll('.delete-art-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const formId = e.currentTarget.dataset.formId;
                        showConfirmationModal(
                            'Delete Artwork',
                            'Are you sure you want to permanently delete this artwork? This will also delete related interpretations and comments. This action cannot be undone.',
                            'Delete Permanently',
                            'danger',
                            () => {
                                document.getElementById(formId).submit();
                            }
                        );
                    });
                });

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
                            '⚠️ PERMANENTLY delete this user? This will delete all their art, comments, and interpretations. This cannot be undone.',
                            'Delete Permanently',
                            'danger',
                            () => {
                                document.getElementById('deleteForm').submit();
                            }
                        );
                    });
                }
            });
            document.addEventListener('DOMContentLoaded', function() {
                const cardViewBtn = document.getElementById('cardViewBtn');
                const tableViewBtn = document.getElementById('tableViewBtn');
                const cardView = document.getElementById('cardView');
                const tableView = document.getElementById('tableView');

                const searchInput = document.getElementById('searchInput');
                const categoryFilter = document.getElementById('categoryFilter');
                const artworkItems = document.querySelectorAll('.artwork-item');

                cardViewBtn.addEventListener('click', () => {
                    cardView.style.display = 'flex';
                    tableView.style.display = 'none';
                    cardViewBtn.classList.add('active');
                    tableViewBtn.classList.remove('active');
                });

                tableViewBtn.addEventListener('click', () => {
                    cardView.style.display = 'none';
                    tableView.style.display = 'block';
                    tableViewBtn.classList.add('active');
                    cardViewBtn.classList.remove('active');
                });

                function filterArtworks() {
                    const searchQuery = searchInput.value.toLowerCase();
                    const category = categoryFilter.value;

                    artworkItems.forEach(item => {
                        const itemCategory = item.dataset.category;
                        const itemTitle = item.dataset.title;
                        const itemArtist = item.dataset.artist;

                        const categoryMatch = (category === "" || itemCategory === category);
                        const searchMatch = (itemTitle.includes(searchQuery) || itemArtist.includes(searchQuery));

                        if (categoryMatch && searchMatch) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }

                searchInput.addEventListener('input', filterArtworks);
                categoryFilter.addEventListener('change', filterArtworks);
            });
        </script>
</body>

</html>