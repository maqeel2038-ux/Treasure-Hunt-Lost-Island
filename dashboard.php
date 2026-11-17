<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT username, coins, score, energy, current_location
     FROM players WHERE player_id = ?"
);

$stmt->execute([$_SESSION["player_id"]]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorer Dashboard | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="dashboard-page">

    <div class="dashboard-container">

        <header class="dashboard-header">

            <div class="brand">
                <h2>🏝️ TREASURE HUNT</h2>
                <p>THE LOST ISLAND</p>
            </div>

            <nav class="dashboard-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="game/island.php">Islands</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="logout.php" class="logout-button">Logout</a>
            </nav>

        </header>

        <section class="welcome-banner">

            <div class="welcome-content">
                <span class="welcome-label">YOUR ADVENTURE CONTINUES</span>

                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($player["username"]); ?>!
                </h1>

                <p>
                    Explore mysterious islands, solve challenging puzzles
                    and discover hidden treasures.
                </p>

                <a href="game/difficulty.php" class="explore-button">
                    START EXPLORING →
                </a>
            </div>

            <div class="welcome-icon">
                🗺️
            </div>

        </section>

        <section class="dashboard-section">

            <div class="section-heading">
                <h2>Your Adventure Statistics</h2>
                <p>Track your progress as an explorer.</p>
            </div>

            <div class="dashboard-stats">

                <div class="dashboard-stat">
                    <span class="stat-icon">🪙</span>
                    <p>GOLD COINS</p>
                    <h2><?php echo (int)$player["coins"]; ?></h2>
                    <span>Treasure collected</span>
                </div>

                <div class="dashboard-stat">
                    <span class="stat-icon">🏆</span>
                    <p>TOTAL SCORE</p>
                    <h2><?php echo (int)$player["score"]; ?></h2>
                    <span>Your achievements</span>
                </div>

                <div class="dashboard-stat">
                    <span class="stat-icon">⚡</span>
                    <p>ENERGY</p>
                    <h2><?php echo (int)$player["energy"]; ?>%</h2>
                    <span>Available energy</span>
                </div>

                <div class="dashboard-stat">
                    <span class="stat-icon">📍</span>
                    <p>CURRENT LOCATION</p>
                    <h2 class="location-value">
                        <?php echo htmlspecialchars($player["current_location"]); ?>
                    </h2>
                    <span>Your current island</span>
                </div>

            </div>

        </section>

        <section class="dashboard-bottom">

            <div class="dashboard-feature">
                <span class="feature-icon">🌴</span>
                <h2>Explore the Islands</h2>
                <p>
                    Discover new locations, face challenges
                    and unlock mysterious destinations.
                </p>
                <a href="game/island.php">Explore Islands →</a>
            </div>

            <div class="dashboard-feature">
                <span class="feature-icon">🏅</span>
                <h2>Global Leaderboard</h2>
                <p>
                    Compare your score with other players
                    and become the top explorer.
                </p>
                <a href="leaderboard.php">View Rankings →</a>
            </div>

        </section>

        <footer class="dashboard-footer">
            TREASURE HUNT: THE LOST ISLAND
            <p>Every explorer has a story. Make yours legendary.</p>
        </footer>

    </div>

</body>
</html>