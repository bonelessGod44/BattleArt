<?php
session_start();
require_once "config.php"; // Connect to the database

//FETCH TOP 4 TRENDING CHALLENGES
$trending_challenges = [];

// This query calculates a "trending_score" by counting all interactions (likes, comments, interpretations)
// and then selects the top 4 challenges with the highest scores.
$sql = "SELECT 
            c.challenge_id,
            c.user_id,
            c.challenge_name,
            c.original_art_filename,
            u.user_userName,
            COUNT(DISTINCT l.like_id) AS like_count,
            COUNT(DISTINCT co.comment_id) AS comment_count,
            (COUNT(DISTINCT l.like_id) + COUNT(DISTINCT co.comment_id) + COUNT(DISTINCT i.interpretation_id)) AS trending_score
        FROM challenges c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN likes l ON c.challenge_id = l.challenge_id
        LEFT JOIN comments co ON c.challenge_id = co.challenge_id
        LEFT JOIN interpretations i ON c.challenge_id = i.challenge_id
        GROUP BY c.challenge_id
        ORDER BY trending_score DESC
        LIMIT 4";

if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $trending_challenges[] = $row;
    }
    $result->free();
}

$viewer_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattleArt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/doro.ico">
    <link rel="stylesheet" href="assets/css/navbar.css">
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
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        .navbar-custom {
            background-color: var(--primary-bg);
            padding: 1rem 1.5rem;
        }

        .navbar-brand-text {
            color: #fff;
            font-weight: bold;
        }

        .nav-link-custom {
            color: #fff;
            font-weight: 500;
        }


        .main-banner-area {
            background-image: url('assets/images/Background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
            padding-top: 2rem;
            padding-bottom: 2rem;
            border-bottom: 5px solid var(--dark-purple-border);
            margin-bottom: 2rem;
        }


        .battleart-title {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            font-size: 4rem;
            font-weight: bold;
            text-align: center;
            margin-top: 3rem;
            margin-bottom: 2rem;
        }


        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 4rem;
        }

        .search-input-group {
            width: 80%;
            max-width: 600px;
        }

        .search-input {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #ced4da;
            box-shadow: none;
        }

        .search-input:focus {
            border-color: var(--primary-bg);
            box-shadow: 0 0 0 0.25rem rgba(140, 118, 236, 0.25);
        }

        .search-icon-btn {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-left: none;
            border-top-right-radius: 50px;
            border-bottom-right-radius: 50px;
            color: #6c757d;
            padding: 0.75rem 1rem;
        }


        .trending-title,
        .features-title {
            color: var(--primary-bg);
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
        }

        .placeholder-card,
        .feature-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
            flex-direction: column;
        }

        .placeholder-card {
            padding: 1rem;
            min-height: 420px;
        }

        .feature-card {
            padding: 1.5rem;
            min-height: 200px;
        }

        .feature-card h5 {
            color: var(--primary-bg);
            margin-bottom: 0.75rem;
            font-weight: bold;
        }

        .feature-card p {
            font-size: 0.95rem;
            color: #555;
        }

        .trending-card-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .trending-card-image {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 0.75rem;
        }

        .trending-card-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            width: 100%;
        }

        .social-item {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .social-icon {
            width: 18px;
            height: 18px;
            margin-right: 0.3rem;
        }

        .btn-challenge-custom {
            background-color: var(--primary-bg);
            border: none;
            color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 500;
            transition: background-color 0.2s ease;
            margin-top: auto;
        }

        .btn-challenge-custom:hover {
            background-color: var(--dark-purple-border);
            color: #fff;
        }

        .logout-btn {
            background-color: #dc3545;
            /* Red */
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s ease;
        }

        .logout-btn:hover {
            background-color: #c82333;
            /* Darker Red */
            color: white;
        }


        /* About section styling */
        .about-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, rgba(140, 118, 236, 0.08) 0%, rgba(166, 231, 255, 0.08) 100%);
            border-radius: 20px;
            margin: 4rem 0;
        }

        .about-title {
            color: var(--primary-bg);
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 3rem;
        }

        .about-content {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .about-intro {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }

        .about-mission {
            font-size: 1.2rem;
            color: var(--primary-bg);
            font-weight: 600;
            font-style: italic;
            margin-bottom: 2.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            border-left: 4px solid var(--primary-bg);
        }

        .how-it-works {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2.5rem 0;
        }

        .step-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-5px);
        }

        .step-number {
            background: var(--primary-bg);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .step-title {
            color: var(--primary-bg);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .value-item {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid var(--light-purple);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .value-item strong {
            color: var(--primary-bg);
            font-size: 1.1rem;
        }

        .cta-section {
            margin-top: 3rem;
        }

        .btn-join-battle {
            background: linear-gradient(135deg, var(--primary-bg) 0%, var(--light-purple) 100%);
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(140, 118, 236, 0.3);
        }

        .btn-join-battle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(140, 118, 236, 0.4);
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .about-section {
                padding: 2rem 0;
                margin: 2rem 0;
            }

            .how-it-works {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }
        }

        .bottom-line {
            height: 5px;
            background-color: var(--dark-purple-border);
            width: 90%;
            margin: 4rem auto 2rem auto;
            border-radius: 5px;
        }

        .btn-signup-custom,
        .btn-login-custom,
        .btn-primary-custom {
            background-color: var(--light-purple);
            border: none;
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-signup-custom:hover,
        .btn-login-custom:hover,
        .btn-primary-custom:hover {
            background-color: #9d83f1;
            color: #fff;
        }

        .btn-signup-custom {
            margin-right: 0.5rem;
        }

        .hero-section {
            background-color: transparent;
            color: #fff;
            padding: 5rem 1rem 0;
            text-align: center;
            margin-top: 0;
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #fff;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            color: #fff;
        }


        .footer-custom {
            background-color: var(--primary-bg);
            color: #fff;
            padding: 2rem 1rem;
            text-align: center;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            margin-top: 4rem;
        }

        .footer-custom p {
            margin-bottom: 0.5rem;
        }

        .footer-custom a {
            color: #fff;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: text-decoration 0.2s ease;
        }

        .footer-custom a:hover {
            text-decoration: underline;
        }

        .search-section-bg {
            background-color: transparent;
            padding-bottom: 2rem;
            margin-bottom: 0;
            padding-top: 0;
        }


        @media (max-width: 767.98px) {
            .navbar-custom {
                padding: 0.75rem 1rem;
            }

            .navbar-toggler {
                margin-right: 0.5rem;
            }

            .navbar-nav .btn {
                margin-top: 0.5rem;
                margin-left: 0;
                width: 100%;
            }

            .battleart-title {
                font-size: 2rem;
                margin-top: 2rem;
                margin-bottom: 1.5rem;
            }

            .search-input-group {
                width: 95%;
            }

            .trending-title,
            .features-title {
                font-size: 1.75rem;
                margin-bottom: 1.5rem;
            }

            .placeholder-card,
            .feature-card {
                height: auto;
                min-height: 150px;
            }

            .hero-section {
                padding: 3rem 1rem 0;
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .search-section-bg {
                padding-bottom: 1.5rem;
                margin-bottom: 0;
            }

            .main-banner-area {
                padding-bottom: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php require 'partials/navbar.php'; ?>
    <div class="main-banner-area">
        <header class="hero-section">
            <div class="container">
                <h1>Unleash Your Creative Power!</h1>
                <p>Discover, create, and share your unique digital art with a vibrant community. Join BattleArt today and turn your imagination into stunning masterpieces.</p>
                <?php
                $button_link = isset($_SESSION['user_id']) ? 'listofarts.php' : 'login.php';
                ?>
                <a href="<?php echo $button_link; ?>" class="btn btn-primary-custom">Start Creating Now</a>
            </div>
        </header>
        <section class="search-section-bg">
            <div class="container">
                <h1 class="battleart-title">BattleArt</h1>

                <div class="search-container">
                    <form action="search.php" method="GET" class="w-100 d-flex justify-content-center">
                        <div class="input-group search-input-group">
                            <input type="text" name="query" class="form-control search-input" placeholder="Search for challenges or users..." aria-label="Search" required>
                            <button class="btn search-icon-btn" type="submit">
                                <img src="assets/images/search.gif" alt="Search Icon" style="width: 16px; height: 16px;">
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div class="container">
        <h2 class="trending-title">TRENDING</h2>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 justify-content-center">
            <?php if (empty($trending_challenges)): ?>
                <div class="col-12">
                    <p class="text-center text-muted">No trending challenges found yet. Be the first to start one!</p>
                </div>
            <?php else: ?>
                <?php foreach ($trending_challenges as $challenge): ?>
                    <div class="col">
                        <div class="placeholder-card">
                            <div class="trending-card-content">
                                <a href="challengepage.php?id=<?php echo $challenge['challenge_id']; ?>">
                                    <img src="assets/uploads/<?php echo htmlspecialchars($challenge['original_art_filename']); ?>" alt="<?php echo htmlspecialchars($challenge['challenge_name']); ?>" class="trending-card-image">
                                </a>
                                <h5 class="trending-card-title"><?php echo htmlspecialchars($challenge['challenge_name']); ?></h5>
                                <p class="text-muted small mb-2">by <?php echo htmlspecialchars($challenge['user_userName']); ?></p>
                                <div class="social-icons">
                                    <div class="social-item">
                                        <i class="fas fa-heart social-icon text-danger"></i>
                                        <span><?php echo $challenge['like_count']; ?></span>
                                    </div>
                                    <div class="social-item">
                                        <i class="fas fa-comment social-icon text-primary"></i>
                                        <span><?php echo $challenge['comment_count']; ?></span>
                                    </div>
                                </div>
                                <?php
                                // Check if the person viewing the page is the owner or a guest
                                if ($viewer_id && $viewer_id == $challenge['user_id']) {
                                    echo '<a href="challengepage.php?id=' . $challenge['challenge_id'] . '" class="btn btn-challenge-custom mt-auto">View Challenge</a>';
                                } else {
                                    $link = $viewer_id ? 'create_interpretation.php?challenge_id=' . $challenge['challenge_id'] : 'login.php';
                                    echo '<a href="' . $link . '" class="btn btn-challenge-custom mt-auto">Challenge!</a>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <hr class="my-5 border-2 border-primary-bg opacity-75">
        <h2 class="features-title">KEY FEATURES</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <div class="col">
                <div class="feature-card">
                    <h5>Post Your Original Art</h5>
                    <p>Share your unique character designs and original artworks for the community to discover.</p>
                </div>
            </div>
            <div class="col">
                <div class="feature-card">
                    <h5>Challenge & Reimagine Characters</h5>
                    <p>Invite others to reinterpret your original characters in their distinct artistic styles.</p>
                </div>
            </div>
            <div class="col">
                <div class="feature-card">
                    <h5>Showcase Diverse Interpretations</h5>
                    <p>See original creations alongside their unique reinterpretations, fostering creative growth.</p>
                </div>
            </div>
            <div class="col">
                <div class="feature-card">
                    <h5>Discover New Perspectives</h5>
                    <p>Explore diverse reinterpretations of art from various artists, broadening your artistic horizons.</p>
                </div>
            </div>
            <div class="col">
                <div class="feature-card">
                    <h5>Grow Your Artistic Skills</h5>
                    <p>Learn new techniques and expand your creative horizons by participating in challenges and critiques.</p>
                </div>
            </div>
            <div class="col">
                <div class="feature-card">
                    <h5>Secure & Supportive Platform</h5>
                    <p>Enjoy a safe, intuitive, and encouraging environment for sharing art and fostering artistic development.</p>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <section class="about-section">
            <div class="container">
                <h2 class="about-title">ABOUT BATTLEART</h2>
                <div class="about-content">
                    <!-- Who we are -->
                    <p class="about-intro">
                        BattleArt is a community for original art and reinterpretations ("art battles").
                        We bring together artists to inspire growth through creative challenges and constructive feedback.
                    </p>

                    <!-- Mission -->
                    <div class="about-mission">
                        "Inspire growth through creative challenges and constructive feedback."
                    </div>

                    <!-- How it works -->
                    <h4 style="color: var(--primary-bg); margin-bottom: 1.5rem;">How It Works</h4>
                    <div class="how-it-works">
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <div class="step-title">Post Original</div>
                            <p>Share your unique character designs and original artwork with the community</p>
                        </div>
                        <div class="step-card">
                            <div class="step-number">2</div>
                            <div class="step-title">Accept Challenges</div>
                            <p>Invite others or accept challenges to reinterpret art in your style</p>
                        </div>
                        <div class="step-card">
                            <div class="step-number">3</div>
                            <div class="step-title">Showcase Side-by-Side</div>
                            <p>Display original and reinterpretations together for comparison and growth</p>
                        </div>
                    </div>

                    <!-- Values -->
                    <h4 style="color: var(--primary-bg); margin-bottom: 1.5rem;">Our Values</h4>
                    <div class="values-grid">
                        <div class="value-item">
                            <strong>Respect</strong><br>
                            Honor every artist's creative journey and perspective
                        </div>
                        <div class="value-item">
                            <strong>Originality</strong><br>
                            Celebrate unique artistic voices and creative expression
                        </div>
                        <div class="value-item">
                            <strong>Attribution</strong><br>
                            Always credit and acknowledge the original creator
                        </div>
                        <div class="value-item">
                            <strong>Supportive Critiques</strong><br>
                            Provide constructive, encouraging, and helpful feedback
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <div class="bottom-line"></div>
    </div>

    <footer class="footer-custom">
        <div class="container">
            <p>&copy; 2025 BattleArt. All rights reserved.</p>
            <div>
                <a href="#">Privacy Policy</a> |
                <a href="#">Terms of Service</a> |
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>