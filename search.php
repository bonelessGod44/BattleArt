<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
// No login required to search, but you can add requireLogin() if you wish.

// Get the search query from the URL
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_term = "%" . $search_query . "%";

// Initialize arrays to hold results
$challenges = [];
$users = [];

// Only perform search if the query is not empty
if (!empty($search_query)) {
    // --- 1. SEARCH FOR MATCHING CHALLENGES ---
    $sql_challenges = "SELECT c.challenge_id, c.challenge_name, c.original_art_filename, u.user_userName 
                       FROM challenges c 
                       JOIN users u ON c.user_id = u.user_id 
                       WHERE c.challenge_name LIKE ? OR u.user_userName LIKE ?";
    if ($stmt_challenges = $mysqli->prepare($sql_challenges)) {
        $stmt_challenges->bind_param("ss", $search_term, $search_term);
        $stmt_challenges->execute();
        $result = $stmt_challenges->get_result();
        while ($row = $result->fetch_assoc()) {
            $challenges[] = $row;
        }
        $stmt_challenges->close();
    }

    // --- 2. SEARCH FOR MATCHING USERS ---
    $sql_users = "SELECT user_id, user_userName, user_profile_pic FROM users WHERE user_userName LIKE ?";
    if ($stmt_users = $mysqli->prepare($sql_users)) {
        $stmt_users->bind_param("s", $search_term);
        $stmt_users->execute();
        $result = $stmt_users->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt_users->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</title>
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
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
        }
        .card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: var(--text-dark);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>

    <?php require 'partials/navbar.php'; ?>

    <div class="container py-5 mt-5">
        <header class="text-center text-white mb-5">
            <h1 class="display-5 fw-bold">Search Results</h1>
            <?php if (!empty($search_query)): ?>
                <p class="lead">Showing results for: <strong class="text-white bg-dark px-2 rounded"><?php echo htmlspecialchars($search_query); ?></strong></p>
            <?php endif; ?>
        </header>

        <!-- Challenges Section -->
        <section class="mb-5">
            <h2 class="h4 fw-bold text-white mb-4">Matching Challenges</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php if (!empty($challenges)): ?>
                    <?php foreach ($challenges as $challenge): ?>
                        <div class="col">
                            <a href="challengepage.php?id=<?php echo $challenge['challenge_id']; ?>" class="card h-100 text-decoration-none">
                                <img src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>" style="height: 180px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h5>
                                    <p class="card-text text-muted small">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
                                    <div class="mt-auto text-end"><i class="fas fa-arrow-right text-primary"></i></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-light text-center">No matching challenges found.</div></div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Users Section -->
        <section>
            <h2 class="h4 fw-bold text-white mb-4">Matching Users</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $found_user): ?>
                        <?php
                            $avatar = !empty($found_user['user_profile_pic']) 
                                ? 'assets/uploads/' . htmlspecialchars($found_user['user_profile_pic']) 
                                : 'assets/images/blank-profile-picture.png';
                        ?>
                        <div class="col">
                            <a href="public_profile.php?user_id=<?php echo $found_user['user_id']; ?>" class="card h-100 text-decoration-none text-center p-3">
                                <img src="<?php echo $avatar; ?>" class="rounded-circle mx-auto" alt="<?php echo htmlspecialchars($found_user['user_userName']); ?>" style="width: 80px; height: 80px; object-fit: cover;">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($found_user['user_userName']); ?></h5>
                                    <p class="btn btn-sm btn-outline-primary mt-2">View Profile</p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12"><div class="alert alert-light text-center">No matching users found.</div></div>
                <?php endif; ?>
            </div>
        </section>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>