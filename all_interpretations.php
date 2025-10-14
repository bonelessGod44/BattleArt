<?php
session_start();
require_once "config.php";
require_once "auth_check.php";
requireLogin();

$challenge_id = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : 0;
if ($challenge_id === 0) {
    die("Error: Invalid Challenge ID provided.");
}

//Fetch the original challenge details to display the name
$challenge_sql = "SELECT challenge_name FROM challenges WHERE challenge_id = ?";
$stmt_challenge = $mysqli->prepare($challenge_sql);
$stmt_challenge->bind_param("i", $challenge_id);
$stmt_challenge->execute();
$challenge = $stmt_challenge->get_result()->fetch_assoc();
$stmt_challenge->close();

if (!$challenge) {
    die("Error: Challenge not found.");
}

//Fetch ALL interpretations for this challenge
$interpretations = [];
$interp_sql = "SELECT i.*, u.user_userName FROM interpretations i JOIN users u ON i.user_id = u.user_id WHERE i.challenge_id = ? ORDER BY i.created_at DESC";
$interp_stmt = $mysqli->prepare($interp_sql);
$interp_stmt->bind_param("i", $challenge_id);
$interp_stmt->execute();
$result = $interp_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $interpretations[] = $row;
}
$interp_stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Interpretations for <?php echo htmlspecialchars($challenge['challenge_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
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
            min-height: 100vh;
        }

        .navbar-custom {
            background-color: var(--primary-bg);
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

        .interpretation-card {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body class="font-sans">

    <nav class="navbar-custom p-4 flex justify-between items-center text-white shadow-lg">
        <div class="flex items-center">
            <a class="flex items-center space-x-3 text-white font-bold text-xl" href="index.php">
                <i class="fas fa-home text-white"></i>
                <span>BattleArt</span>
            </a>
        </div>
        <div class="flex items-center space-x-4">
            <a class="nav-link-custom flex items-center space-x-2 text-white font-medium" href="#">
                <i class="fas fa-inbox"></i>
                <span>Inbox</span>
            </a>
            <a class="nav-link-custom flex items-center space-x-2 text-white font-medium" href="profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a class="nav-link-custom flex items-center space-x-2 text-white font-medium" href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <div class="container mx-auto p-8 my-8">
        <header class="text-center mb-8 bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800">All Interpretations</h1>
            <p class="text-gray-600 mt-2">For the challenge: <strong class="text-purple-600"><?php echo htmlspecialchars($challenge['challenge_name']); ?></strong></p>
        </header>

        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

                <?php if (empty($interpretations)): ?>
                    <div class="col-span-full text-center py-10 bg-white/20 rounded-lg">
                        <p class="text-white font-semibold">No interpretations have been submitted for this challenge yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($interpretations as $interp): ?>
                        <div class="card interpretation-card p-4 rounded-lg shadow-md flex flex-col">
                            <div class="flex items-center space-x-3 mb-2">
                                <i class="fas fa-user-circle text-2xl text-purple-600"></i>
                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($interp['user_userName']); ?></span>
                            </div>
                            <img class="w-full h-auto rounded-lg shadow-sm mb-3" src="assets/uploads/<?php echo htmlspecialchars($interp['art_filename']); ?>" alt="Interpretation">
                            <?php
                            if (!empty($interp['description'])) {
                                echo '"' . htmlspecialchars($interp['description']) . '"';
                            }
                            ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </section>

        <a href="challengepage.php?id=<?php echo $challenge_id; ?>" class="fixed bottom-8 right-8 z-50 bg-white text-gray-700 py-2 px-6 rounded-full font-bold shadow-lg hover:bg-gray-200 transition flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Challenge Page
        </a>
    </div>
</body>

</html>