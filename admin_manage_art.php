<?php
require_once "admin_auth_check.php";
requireAdmin();

// Handle art deletion
if (isset($_POST['delete_art'])) {
    $art_id = (int)$_POST['art_id'];

    // Delete artwork file
    $sql = "SELECT original_art_filename FROM challenges WHERE challenge_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $art_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($art = $result->fetch_assoc()) {
            $filepath = 'assets/uploads/' . $art['original_art_filename'];
            if (file_exists($filepath)) unlink($filepath);
        }
        $stmt->close();
    }

    // Delete from database
    $sql = "DELETE FROM challenges WHERE challenge_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $art_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_manage_art.php");
    exit();
}

// Fetch artworks
$artworks = [];
$sql = "SELECT c.*, u.user_userName, u.user_profile_pic,
        (SELECT COUNT(*) FROM interpretations WHERE challenge_id = c.challenge_id) as interpretation_count,
        (SELECT COUNT(*) FROM likes WHERE challenge_id = c.challenge_id) as like_count
        FROM challenges c 
        JOIN users u ON c.user_id = u.user_id 
        ORDER BY c.created_at DESC";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $artworks[] = $row;
    $result->free();
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.navbar-brand-text {
    color: #fff;
    font-weight: bold;
    font-size: 1.25rem;
}
.nav-link-custom {
    color: #fff !important;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}
.nav-link-custom:hover {
    background-color: rgba(255,255,255,0.1);
}
.dashboard-container {
    max-width: 1400px;
    margin: 3rem auto;
    background: #fff;
    padding: 2.5rem;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.art-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(140,118,236,0.2);
}
.art-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}
.art-card .card-body {
    padding: 1rem 1.25rem;
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
    width: 30px;
    height: 30px;
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
}
.btn-danger-custom:hover {
    background-color: #ff5252;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255,107,107,0.3);
}
@media (max-width:768px){
    .dashboard-container{margin:1.5rem;padding:1.5rem;}
}
</style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/home.png" alt="Home" class="me-2" width="24" height="24">
            <span class="navbar-brand-text">BattleArt</span>
        </a>
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="nav-link nav-link-custom me-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="admin_manage_comments.php" class="nav-link nav-link-custom me-3"><i class="bi bi-chat-dots me-1"></i> Comments</a>
            <div class="dropdown">
                <button class="btn btn-link nav-link-custom dropdown-toggle" id="adminDropdown" data-bs-toggle="dropdown">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default_avatar.png'); ?>"
                         alt="Admin Avatar" class="rounded-circle me-1"
                         style="width:32px;height:32px;object-fit:cover;">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <h2><i class="bi bi-images me-2"></i>Manage Artworks</h2>

    <!-- Filter Section -->
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

    <!-- Card View -->
    <div id="cardView" class="row g-4">
        <?php if (empty($artworks)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-image" style="font-size:4rem;color:#ccc;"></i>
                <p class="text-muted mt-3">No artworks found</p>
            </div>
        <?php else: ?>
            <?php foreach ($artworks as $art): ?>
            <div class="col-lg-4 col-md-6 artwork-item" data-category="<?php echo htmlspecialchars($art['category'] ?? ''); ?>">
                <div class="card art-card">
                    <img src="assets/uploads/<?php echo htmlspecialchars($art['original_art_filename']); ?>" class="card-img-top" alt="Artwork">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($art['challenge_name']); ?></h5>
                        <div class="artist-info">
                            <img src="<?php echo htmlspecialchars($art['user_profile_pic'] ?? 'assets/images/default_avatar.png'); ?>" alt="Artist" class="artist-avatar">
                            <span class="text-muted"><?php echo htmlspecialchars($art['user_userName']); ?></span>
                        </div>
                        <div class="d-flex justify-content-start align-items-center mb-3">
                            <span class="stats-badge"><i class="bi bi-heart-fill text-danger"></i><?php echo $art['like_count']; ?></span>
                            <span class="stats-badge"><i class="bi bi-palette-fill text-primary"></i><?php echo $art['interpretation_count']; ?></span>
                        </div>
                        <form method="POST" onsubmit="return confirm('⚠️ Are you sure you want to delete this artwork? This action cannot be undone.');">
                            <input type="hidden" name="art_id" value="<?php echo $art['challenge_id']; ?>">
                            <button type="submit" name="delete_art" class="btn btn-danger-custom w-100">
                                <i class="bi bi-trash me-1"></i> Delete Artwork
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Table View (hidden initially) -->
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
                    <tr><td colspan="8" class="text-center py-4 text-muted">No artworks found</td></tr>
                <?php else: ?>
                    <?php foreach ($artworks as $art): ?>
                    <tr data-category="<?php echo htmlspecialchars($art['category'] ?? ''); ?>">
                        <td><img src="assets/uploads/<?php echo htmlspecialchars($art['original_art_filename']); ?>" class="rounded" width="80" height="80" style="object-fit:cover;"></td>
                        <td><strong><?php echo htmlspecialchars($art['challenge_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($art['user_userName']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($art['category'] ?? 'N/A'); ?></span></td>
                        <td><i class="bi bi-heart-fill text-danger"></i> <?php echo $art['like_count']; ?></td>
                        <td><i class="bi bi-palette-fill text-primary"></i> <?php echo $art['interpretation_count']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($art['created_at'])); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('⚠️ Delete this artwork?');">
                                <input type="hidden" name="art_id" value="<?php echo $art['challenge_id']; ?>">
                                <button type="submit" name="delete_art" class="btn btn-danger-custom btn-sm">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/art-filter.js"></script>
</body>
</html>
