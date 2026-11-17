
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit;
}

// Get daily reward information
$stmt = $pdo->prepare("
    SELECT last_claim_date, login_streak
    FROM daily_rewards
    WHERE player_id = ?
");

$stmt->execute([$_SESSION["player_id"]]);
$dailyReward = $stmt->fetch(PDO::FETCH_ASSOC);

$today = date("Y-m-d");

$claimedToday = (
    $dailyReward &&
    $dailyReward["last_claim_date"] === $today
);

$currentStreak = $dailyReward
    ? (int)$dailyReward["login_streak"]
    : 0;

// Get player information
$stmt = $pdo->prepare("
    SELECT username, coins, score, energy, current_location
    FROM players
    WHERE player_id = ?
");

$stmt->execute([$_SESSION["player_id"]]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get all achievements and the player's earned achievements
$stmt = $pdo->prepare("
    SELECT
        a.achievement_id,
        a.achievement_name,
        a.description,
        a.badge_icon,
        a.requirement_type,
        a.requirement_value,
        pa.earned_at
    FROM achievements a
    LEFT JOIN player_achievements pa
        ON a.achievement_id = pa.achievement_id
        AND pa.player_id = ?
    ORDER BY a.achievement_id ASC
");

$stmt->execute([$_SESSION["player_id"]]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count earned achievements
$earnedAchievements = 0;

foreach ($achievements as $achievement) {
    if ($achievement["earned_at"] !== null) {
        $earnedAchievements++;
    }
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


<!-- Achievements Section -->
<section class="dashboard-section achievements-section">

    <div class="section-heading">
        <h2>🏅 Explorer Achievements</h2>
        <p>
            Collect badges by completing challenges and exploring the islands.
        </p>
        <p>
            <strong>
                <?php echo $earnedAchievements; ?>
                / <?php echo count($achievements); ?>
            </strong>
            Achievements Unlocked
        </p>
    </div>

    <div class="dashboard-stats">

        <?php foreach ($achievements as $achievement): ?>

            <?php
                $isEarned = $achievement["earned_at"] !== null;
            ?>

            <div class="dashboard-stat achievement-card">

                <span class="stat-icon">
                    <?php echo htmlspecialchars($achievement["badge_icon"]); ?>
                </span>

                <p>
                    <?php echo $isEarned ? "UNLOCKED" : "LOCKED"; ?>
                </p>

                <h2>
                    <?php echo htmlspecialchars($achievement["achievement_name"]); ?>
                </h2>

                <span>
                    <?php echo htmlspecialchars($achievement["description"]); ?>
                </span>

                <?php if ($isEarned): ?>

                    <p style="color: #28a745; margin-top: 12px;">
                        ✓ Achievement Earned
                    </p>

                <?php else: ?>

                    <p style="color: #aaa; margin-top: 12px;">
                        🔒 Not Unlocked Yet
                    </p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</section>

        <!-- Daily Rewards Section -->
<section class="dashboard-section daily-rewards-section">

    <div class="section-heading">
        <h2>🎁 Daily Explorer Rewards</h2>
        <p>Return every day to collect coins and build your login streak!</p>
    </div>

    <div class="dashboard-feature">

        <span class="feature-icon">🎁</span>

        <h2>Claim Your Daily Reward</h2>

        <p>
            Collect 50 free coins every day.
            Earn an additional 50 coins every 7 consecutive days!
        </p>

        <button
    type="button"
    id="claimRewardBtn"
    class="explore-button"
    <?php echo $claimedToday ? 'disabled' : ''; ?>
>
    <?php echo $claimedToday
        ? '✓ REWARD CLAIMED'
        : '🎁 CLAIM DAILY REWARD'; ?>
</button>

        <p id="rewardMessage" style="margin-top:15px;"></p>

        <p id="streakDisplay">
    🔥 Login streak:
    <strong><?php echo $currentStreak; ?> days</strong>
</p>

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

    <script>

const claimRewardBtn = document.getElementById("claimRewardBtn");
const rewardMessage = document.getElementById("rewardMessage");
const streakDisplay = document.getElementById("streakDisplay");

claimRewardBtn.addEventListener("click", async function() {

    claimRewardBtn.disabled = true;
    claimRewardBtn.textContent = "CLAIMING...";

    try {

        const response = await fetch("api/daily_reward.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not claim reward.");
        }

        rewardMessage.textContent =
            "🏆 " + data.message +
            " You received " + data.reward + " coins!";

        streakDisplay.innerHTML =
            "🔥 Login streak: <strong>" + data.streak + " days</strong>";

        claimRewardBtn.textContent = "✓ REWARD CLAIMED";
        claimRewardBtn.disabled = true;

        // Update displayed coins
        location.reload();

    } catch (error) {

        rewardMessage.textContent = "⚠️ " + error.message;

        claimRewardBtn.disabled = false;
        claimRewardBtn.textContent = "🎁 CLAIM DAILY REWARD";
    }

});

</script>

</body>
</html>