<?php
session_start();
require_once "config.php"; // Your database connection
require_once "auth_check.php"; // Your authentication functions

// Ensure only logged-in users can access this page
requireLogin();

//DETERMINE THE FILTER
//Check if a category is selected from the URL, e.g., listofarts.php?category=fantasy
$selected_category = $_GET['category'] ?? null;

//FETCH CHALLENGES FROM THE DATABASE
$challenges = [];
$sql = "SELECT 
            c.*, 
            u.user_userName,
            COUNT(i.interpretation_id) AS interpretation_count
        FROM challenges c 
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN interpretations i ON c.challenge_id = i.challenge_id";

// If a category is selected, add a WHERE clause to filter the results
if ($selected_category) {
    $sql .= " WHERE c.category = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $selected_category);
} else {
    // If no category is selected, fetch all challenges
    $stmt = $mysqli->prepare($sql);
}

// Add the GROUP BY and ORDER BY clauses
$sql .= " GROUP BY c.challenge_id ORDER BY c.created_at DESC";

if ($selected_category) {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $selected_category);
} else {
    $stmt = $mysqli->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $challenges[] = $row;
}
$stmt->close();
$mysqli->close();

// Define categories for easy looping in the HTML
$categories = [
    ['name' => 'Digital Painting', 'value' => 'digital_painting', 'icon' => 'fa-brush'],
    ['name' => 'Sci-Fi',           'value' => 'sci-fi',           'icon' => 'fa-robot'],
    ['name' => 'Fantasy',          'value' => 'fantasy',          'icon' => 'fa-dragon'],
    ['name' => 'Abstract',         'value' => 'abstract',         'icon' => 'fa-palette'],
    ['name' => 'Portraits',        'value' => 'portraits',        'icon' => 'fa-users'],
    ['name' => 'Landscapes',       'value' => 'landscapes',       'icon' => 'fa-mountain']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt Challenges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            background-image: linear-gradient(to bottom, var(--secondary-bg), var(--light-purple));
            font-family: 'Inter', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            padding-top: 20px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: var(--text-dark);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-challenge {
            background-color: var(--primary-bg);
            transition: background-color 0.2s ease;
        }

        .btn-challenge:hover {
            background-color: #7b68ee;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-blue-300 to-purple-400 min-h-screen font-sans">
    <?php require 'partials/navbar.php'; ?>
    <!-- Main Content Container -->
    <div class="container mx-auto p-8 my-8">
        <header class="flex flex-col sm:flex-row justify-between items-center mb-8 space-y-4 sm:space-y-0">
            <h1 class="text-3xl font-bold text-white text-center sm:text-left">Creative Challenges</h1>
            <a href="createchallenge.php" class="btn-challenge text-white py-2 px-6 rounded-full font-bold shadow-lg">
                <i class="fas fa-plus mr-2"></i> Create Challenge
            </a>
        </header>

        <!-- Categories Section -->
        <section class="mb-12">
            <h2 class="text-3xl font-bold text-white mb-6 text-center">Browse by Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">

                <?php foreach ($categories as $category): ?>
                    <?php
                    // Determine the link: if this category is already selected, the link will reset the filter.
                    $link = 'listofarts.php?category=' . $category['value'];
                    if ($selected_category === $category['value']) {
                        $link = 'listofarts.php'; // Link to reset the filter
                    }
                    // Add a visual indicator for the active category
                    $activeClass = ($selected_category === $category['value']) ? 'ring-4 ring-purple-400' : '';
                    ?>
                    <a href="<?php echo $link; ?>" class="card p-4 text-center <?php echo $activeClass; ?>">
                        <i class="fas <?php echo $category['icon']; ?> text-3xl text-purple-600 mb-2"></i>
                        <p class="font-semibold"><?php echo $category['name']; ?></p>
                    </a>
                <?php endforeach; ?>

            </div>
        </section>

        <!-- Art Challenge Grid -->
        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                <?php if (!empty($challenges)): ?>
                    <?php foreach ($challenges as $challenge): ?>
                        <a href="challengepage.php?id=<?php echo $challenge['challenge_id']; ?>" class="card p-4 rounded-lg shadow-md block">
                            <img class="w-full h-48 object-cover rounded-lg mb-2" src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>">
                            <h3 class="font-bold text-xl mb-1"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 text-sm"><i class="fas fa-paint-brush mr-1"></i> <?php echo $challenge['interpretation_count']; ?> Reinterpretations</span> <i class="fas fa-arrow-right text-purple-600"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10 bg-white/20 rounded-lg">
                        <p class="text-white font-semibold">No challenges found for this category.</p>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </div>
</body>

</html>