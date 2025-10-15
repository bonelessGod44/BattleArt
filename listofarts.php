<?php
session_start();
require_once "config.php";
require_once "auth_check.php";

// Ensure only logged-in users can access this page
requireLogin();

$selected_category = $_GET['category'] ?? null;

// --- CORRECTED & CLEANED UP SQL LOGIC ---
$challenges = [];
$sql = "SELECT 
            c.*, 
            u.user_userName,
            COUNT(i.interpretation_id) AS interpretation_count
        FROM challenges c 
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN interpretations i ON c.challenge_id = i.challenge_id";

// Add the WHERE clause if a category is selected
if ($selected_category) {
    $sql .= " WHERE c.category = ?";
}

// Add the GROUP BY and ORDER BY clauses
$sql .= " GROUP BY c.challenge_id ORDER BY c.created_at DESC";

// Prepare and execute the statement just once
if ($stmt = $mysqli->prepare($sql)) {
    if ($selected_category) {
        $stmt->bind_param("s", $selected_category);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $challenges[] = $row;
    }
    $stmt->close();
}

// Define categories for easy looping
$categories = [
    ['name' => 'Digital Painting', 'value' => 'digital_painting', 'icon' => 'fa-brush'],
    ['name' => 'Sci-Fi', 'value' => 'sci-fi', 'icon' => 'fa-robot'],
    ['name' => 'Fantasy', 'value' => 'fantasy', 'icon' => 'fa-dragon'],
    ['name' => 'Abstract', 'value' => 'abstract', 'icon' => 'fa-palette'],
    ['name' => 'Portraits', 'value' => 'portraits', 'icon' => 'fa-users'],
    ['name' => 'Landscapes', 'value' => 'landscapes', 'icon' => 'fa-mountain']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt Challenges</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <style>
        :root {
            --primary-bg: #8c76ec;
            --secondary-bg: #a6e7ff;
            --light-purple: #c3b4fc;
            --text-dark: #333;
        }

        body {
            background-image: linear-gradient(to bottom, #a6e7ff, #c3b4fc);
            min-height: 100vh;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            padding-top: 20px;
        }

        .card {
            border-radius: 20px;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .btn-challenge-create {
            background-color: var(--primary-bg);
            color: white;
            transition: background-color 0.2s ease;
        }

        .btn-challenge-create:hover {
            background-color: #7b68ee;
            color: white;
        }

        .category-card.active {
            border: 3px solid var(--primary-bg);
        }
    </style>
</head>

<body class="font-sans">
    <?php require 'partials/navbar.php'; ?>

    <div class="container my-5">
        <header class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-5">
            <h1 class="display-5 fw-bold text-white text-center text-sm-start mb-3 mb-sm-0">Creative Challenges</h1>
            <a href="createchallenge.php" class="btn btn-challenge-create rounded-pill px-4 py-2 fw-bold shadow-lg">
                <i class="fas fa-plus me-2"></i> Create Challenge
            </a>
        </header>

        <!-- Categories Section -->
        <section class="mb-5">
            <h2 class="h4 fw-bold text-white mb-4 text-center">Browse by Category</h2>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $link = 'listofarts.php?category=' . $category['value'];
                    if ($selected_category === $category['value']) {
                        $link = 'listofarts.php';
                    }
                    $activeClass = ($selected_category === $category['value']) ? 'active' : '';
                    ?>
                    <div class="col">
                        <a href="<?php echo $link; ?>" class="card category-card text-center p-3 text-decoration-none shadow-sm <?php echo $activeClass; ?>">
                            <i class="fas <?php echo $category['icon']; ?> fs-2 mb-2" style="color: var(--primary-bg);"></i>
                            <p class="fw-semibold mb-0"><?php echo $category['name']; ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Art Challenge Grid -->
        <section>
            <h2 class="h4 fw-bold text-white mb-4 text-center">
                <?php echo $selected_category ? ucwords(str_replace('_', ' ', $selected_category)) . ' Challenges' : 'All Challenges'; ?>
            </h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                <?php if (!empty($challenges)): ?>
                    <?php foreach ($challenges as $challenge): ?>
                        <div class="col">
                            <a href="challengepage.php?id=<?php echo $challenge['challenge_id']; ?>" class="card h-100 text-decoration-none shadow-sm">
                                <img class="card-img-top" src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>" style="height: 180px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h5>
                                    <p class="card-text text-muted small mb-2">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                                        <span class="text-muted small"><i class="fas fa-paint-brush me-1"></i> <?php echo $challenge['interpretation_count']; ?> Reinterpretations</span>
                                        <i class="fas fa-arrow-right" style="color: var(--primary-bg);"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-light text-center">
                            No challenges found for this category.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>