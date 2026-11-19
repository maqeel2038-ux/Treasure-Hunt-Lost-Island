
<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit;
}

$playerId = (int)$_SESSION["player_id"];

// Daily reward information
$stmt = $pdo->prepare("
    SELECT last_claim_date, login_streak
    FROM daily_rewards
    WHERE player_id = ?
");
$stmt->execute([$playerId]);
$dailyReward = $stmt->fetch(PDO::FETCH_ASSOC);

$today = date("Y-m-d");

$claimedToday = (
    $dailyReward &&
    $dailyReward["last_claim_date"] === $today
);

$currentStreak = $dailyReward
    ? (int)$dailyReward["login_streak"]
    : 0;

// Player information
$stmt = $pdo->prepare("
    SELECT username, coins, score, energy, current_location
    FROM players
    WHERE player_id = ?
");
$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Achievements
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
$stmt->execute([$playerId]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$earnedAchievements = 0;

foreach ($achievements as $achievement) {
    if ($achievement["earned_at"] !== null) {
        $earnedAchievements++;
    }
}

$totalAchievements = count($achievements);

$achievementPercentage = $totalAchievements > 0
    ? round(($earnedAchievements / $totalAchievements) * 100)
    : 0;

$energy = max(0, min(100, (int)$player["energy"]));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Explorer Dashboard | Treasure Hunt</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body.dashboard-page {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 80% 5%, rgba(44,91,145,0.18), transparent 35%),
                #101D38;
            color: #fff;
            font-family: 'Outfit', sans-serif;
        }

        .dashboard-container {
            max-width: 1250px;
            width: 92%;
            margin: auto;
            padding-bottom: 35px;
        }

        /* Navigation */

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
            padding: 22px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            color: #FFC629;
        }

        .brand p {
            margin: 5px 0 0;
            color: #91a3c1;
            font-size: 11px;
            letter-spacing: 3px;
        }

        .dashboard-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dashboard-nav a {
            text-decoration: none;
            color: #bdcbe0;
            padding: 10px 14px;
            border-radius: 9px;
            font-size: 14px;
            transition: 0.2s;
        }

        .dashboard-nav a:hover,
        .dashboard-nav a.active {
            background: rgba(255,198,41,0.1);
            color: #FFC629;
        }

        .dashboard-nav .logout-button {
            border: 1px solid rgba(255,100,100,0.25);
            color: #ffaaaa;
        }

        .dashboard-nav .logout-button:hover {
            background: rgba(255,80,80,0.1);
        }

        /* Welcome Hero */

        .welcome-banner {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            margin-top: 30px;
            padding: 45px;
            border: 1px solid rgba(255,198,41,0.2);
            border-radius: 24px;
            background:
                radial-gradient(circle at 90% 50%, rgba(255,198,41,0.12), transparent 30%),
                linear-gradient(135deg, #263A5D, #172947 75%, #101D38);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            max-width: 650px;
        }

        .welcome-label {
            display: inline-block;
            color: #FFC629;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }

        .welcome-content h1 {
            font-size: clamp(28px, 5vw, 45px);
            line-height: 1.2;
            margin: 0 0 15px;
            font-weight: 800;
        }

        .welcome-content h1 span {
            color: #FFC629;
        }

        .welcome-content p {
            color: #b8c7dc;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .explore-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #FFD84D, #FFC629);
            color: #101D38;
            text-decoration: none;
            border: none;
            border-radius: 11px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.25s;
        }

        .explore-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,198,41,0.25);
        }

        .explore-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .welcome-icon {
            font-size: 115px;
            opacity: 0.95;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
            animation: mapFloat 4s ease-in-out infinite;
        }

        @keyframes mapFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* Section headings */

        .dashboard-section {
            margin-top: 40px;
        }

        .section-heading {
            margin-bottom: 22px;
        }

        .section-heading h2 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
        }

        .section-heading p {
            color: #91a3c1;
            margin: 0;
            line-height: 1.6;
        }

        /* Statistics */

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .dashboard-stat {
            padding: 23px;
            border-radius: 17px;
            background: #263A5D;
            border: 1px solid rgba(255,255,255,0.06);
            transition: 0.25s;
        }

        .dashboard-stat:hover {
            transform: translateY(-4px);
            border-color: rgba(255,198,41,0.25);
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            margin-bottom: 15px;
            border-radius: 14px;
            background: rgba(255,198,41,0.1);
            font-size: 24px;
        }

        .dashboard-stat > p {
            margin: 0 0 8px;
            color: #91a3c1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .dashboard-stat h2 {
            margin: 0 0 8px;
            color: #FFC629;
            font-size: 30px;
            font-weight: 900;
        }

        .dashboard-stat > span:last-child {
            color: #a9bbd5;
            font-size: 13px;
        }

        .location-value {
            font-size: 19px !important;
            word-break: break-word;
        }

        /* Energy progress */

        .energy-track {
            height: 7px;
            background: #101D38;
            border-radius: 20px;
            overflow: hidden;
            margin: 12px 0;
        }

        .energy-fill {
            height: 100%;
            width: <?php echo $energy; ?>%;
            background: linear-gradient(90deg, #21c77a, #8affb8);
            border-radius: 20px;
            transition: width 0.5s;
        }

        /* Achievements */

        .achievement-progress {
            margin-top: 18px;
            padding: 18px;
            border-radius: 14px;
            background: #263A5D;
        }

        .achievement-progress-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            color: #d7e2f1;
            font-size: 14px;
        }

        .achievement-progress-top strong {
            color: #FFC629;
        }

        .achievement-track {
            height: 9px;
            border-radius: 20px;
            overflow: hidden;
            background: #101D38;
        }

        .achievement-fill {
            width: <?php echo $achievementPercentage; ?>%;
            height: 100%;
            background: linear-gradient(90deg, #FFC629, #ffe68a);
            border-radius: 20px;
        }

        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .achievement-card {
            text-align: center;
            padding: 22px 17px;
        }

        .achievement-card.locked {
            opacity: 0.65;
        }

        .achievement-card .stat-icon {
            font-size: 35px;
            width: 62px;
            height: 62px;
        }

        .achievement-card h3 {
            margin: 8px 0;
            font-size: 17px;
            color: #fff;
        }

        .achievement-card .achievement-description {
            display: block;
            min-height: 38px;
            color: #a9bbd5;
            font-size: 13px;
            line-height: 1.6;
        }

        .achievement-status {
            margin-top: 15px;
            font-size: 12px;
            font-weight: 800;
        }

        .achievement-card.unlocked {
            border-color: rgba(255,198,41,0.3);
        }

        .achievement-card.unlocked .achievement-status {
            color: #FFC629;
        }

        .achievement-card.locked .achievement-status {
            color: #91a3c1;
        }

        /* Daily rewards */

        .daily-reward-card {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            flex-wrap: wrap;
            padding: 30px;
            border: 1px solid rgba(255,198,41,0.25);
            border-radius: 20px;
            background:
                radial-gradient(circle at 100% 0%, rgba(255,198,41,0.12), transparent 35%),
                linear-gradient(135deg, #263A5D, #1c304f);
        }

        .daily-reward-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 85px;
            height: 85px;
            flex-shrink: 0;
            border-radius: 22px;
            background: rgba(255,198,41,0.12);
            border: 1px solid rgba(255,198,41,0.2);
            font-size: 48px;
        }

        .daily-reward-content {
            flex: 1;
            min-width: 220px;
        }

        .daily-reward-content h2 {
            margin: 0 0 10px;
            font-size: 23px;
        }

        .daily-reward-content > p {
            color: #b8c7dc;
            line-height: 1.7;
            margin: 0;
        }

        .reward-details {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .reward-amount,
        .reward-bonus {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
            border-radius: 11px;
            background: rgba(16,29,56,0.65);
            border: 1px solid rgba(255,255,255,0.06);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        .reward-amount strong {
            color: #FFC629;
            font-size: 19px;
        }

        .reward-bonus strong {
            color: #8affb8;
        }

        .reward-streak {
            margin-top: 17px !important;
            color: #FFC629 !important;
            font-weight: 700;
        }

        .reward-streak strong {
            color: #fff;
        }

        .reward-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .reward-action .explore-button {
            white-space: nowrap;
            min-width: 205px;
        }

        .reward-action .explore-button:disabled {
            background: #3c506e;
            color: #b8c7dc;
            opacity: 1;
        }

        #rewardMessage {
            margin: 0;
            min-height: 18px;
            color: #8affb8;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
        }

        /* Quick links */

        .dashboard-bottom {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 35px;
        }

        .dashboard-feature {
            position: relative;
            padding: 28px;
            background: #263A5D;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 18px;
            transition: 0.25s;
        }

        .dashboard-feature:hover {
            transform: translateY(-4px);
            border-color: rgba(255,198,41,0.3);
        }

        .feature-icon {
            font-size: 35px;
            display: block;
            margin-bottom: 15px;
        }

        .dashboard-feature h2 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        .dashboard-feature p {
            color: #a9bbd5;
            line-height: 1.7;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .dashboard-feature a {
            color: #FFC629;
            text-decoration: none;
            font-weight: 800;
        }

        .dashboard-feature a:hover {
            color: #ffe68a;
        }

        /* Footer */

        .dashboard-footer {
            text-align: center;
            padding: 35px 10px 10px;
            color: #7185a6;
            font-size: 12px;
            letter-spacing: 1.5px;
        }

        .dashboard-footer p {
            letter-spacing: normal;
            margin-top: 10px;
        }

        /* Responsive */

        @media (max-width: 950px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .achievement-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .daily-reward-card {
                align-items: flex-start;
            }
        }

        @media (max-width: 650px) {
            .dashboard-container {
                width: 94%;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dashboard-nav {
                width: 100%;
            }

            .dashboard-nav a {
                padding: 8px 10px;
                font-size: 12px;
            }

            .welcome-banner {
                padding: 28px 22px;
            }

            .welcome-icon {
                font-size: 65px;
            }

            .welcome-content p {
                font-size: 14px;
            }

            .dashboard-stats {
                gap: 12px;
            }

            .dashboard-stat {
                padding: 17px;
            }

            .dashboard-stat h2 {
                font-size: 25px;
            }

            .achievement-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-bottom {
                grid-template-columns: 1fr;
            }

            .daily-reward-card {
                padding: 22px;
                flex-direction: column;
            }

            .daily-reward-icon {
                width: 65px;
                height: 65px;
                font-size: 38px;
            }

            .daily-reward-content {
                width: 100%;
            }

            .reward-action {
                width: 100%;
                align-items: stretch;
            }

            .reward-action .explore-button {
                width: 100%;
            }

            #rewardMessage {
                text-align: left;
            }
        }

        @media (max-width: 380px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .welcome-icon {
                display: none;
            }

            .reward-details {
                align-items: stretch;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body class="dashboard-page">

<div class="dashboard-container">

    <!-- Navigation -->
    <header class="dashboard-header">

        <div class="brand">
            <h2>🏝️ TREASURE HUNT</h2>
            <p>THE LOST ISLAND</p>
        </div>

        <nav class="dashboard-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="game/island.php">Islands</a>
            <a href="leaderboard.php">Leaderboard</a>
            <a href="profile.php">My Profile</a>
            <a href="logout.php" class="logout-button">Logout</a>
        </nav>

    </header>

    <!-- Welcome -->
    <section class="welcome-banner">

        <div class="welcome-content">

            <span class="welcome-label">
                YOUR ADVENTURE CONTINUES
            </span>

            <h1>
                Welcome back,<br>
                <span><?php echo htmlspecialchars($player["username"]); ?>!</span>
            </h1>

            <p>
                Explore mysterious islands, solve challenging puzzles
                and discover hidden treasures.
                Your next adventure awaits!
            </p>

            <a href="game/island.php" class="explore-button">
    🗺️ START EXPLORING
</a>

        </div>

        <div class="welcome-icon">🗺️</div>

    </section>

    <!-- Stats -->
    <section class="dashboard-section">

        <div class="section-heading">
            <h2>📊 Your Adventure Statistics</h2>
            <p>Track your progress as an explorer.</p>
        </div>

        <div class="dashboard-stats">

            <div class="dashboard-stat">
                <span class="stat-icon">🪙</span>
                <p>GOLD COINS</p>
                <h2><?php echo number_format((int)$player["coins"]); ?></h2>
                <span>Treasure collected</span>
            </div>

            <div class="dashboard-stat">
                <span class="stat-icon">🏆</span>
                <p>TOTAL SCORE</p>
                <h2><?php echo number_format((int)$player["score"]); ?></h2>
                <span>Your achievements</span>
            </div>

            <div class="dashboard-stat">
                <span class="stat-icon">⚡</span>
                <p>ENERGY</p>
                <h2><?php echo $energy; ?>%</h2>

                <div class="energy-track">
                    <div class="energy-fill"></div>
                </div>

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

    <!-- Achievements -->
    <section class="dashboard-section">

        <div class="section-heading">
            <h2>🏅 Explorer Achievements</h2>
            <p>Collect badges by completing challenges and exploring the islands.</p>
        </div>

        <div class="achievement-progress">

            <div class="achievement-progress-top">
                <span>Achievement Progress</span>
                <strong>
                    <?php echo $earnedAchievements; ?>/<?php echo $totalAchievements; ?>
                    (<?php echo $achievementPercentage; ?>%)
                </strong>
            </div>

            <div class="achievement-track">
                <div class="achievement-fill"></div>
            </div>

        </div>

        <div class="achievement-grid">

            <?php foreach ($achievements as $achievement): ?>

                <?php $isEarned = $achievement["earned_at"] !== null; ?>

                <div class="dashboard-stat achievement-card
                    <?php echo $isEarned ? 'unlocked' : 'locked'; ?>">

                    <span class="stat-icon">
                        <?php echo htmlspecialchars($achievement["badge_icon"]); ?>
                    </span>

                    <h3>
                        <?php echo htmlspecialchars($achievement["achievement_name"]); ?>
                    </h3>

                    <span class="achievement-description">
                        <?php echo htmlspecialchars($achievement["description"]); ?>
                    </span>

                    <div class="achievement-status">
                        <?php echo $isEarned
                            ? '✓ ACHIEVEMENT UNLOCKED'
                            : '🔒 LOCKED'; ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- Daily Rewards -->
    <section class="dashboard-section">

        <div class="section-heading">
            <h2>🎁 Daily Explorer Rewards</h2>
            <p>Return every day to collect coins and build your login streak!</p>
        </div>

        <div class="daily-reward-card">

            <div class="daily-reward-icon">🎁</div>

            <div class="daily-reward-content">

                <h2>Claim Your Daily Reward</h2>

                <p>
                    Collect 50 free coins every day.
                    Keep your streak going to unlock extra rewards!
                </p>

                <div class="reward-details">

                    <div class="reward-amount">
                        🪙 DAILY REWARD
                        <strong>+50</strong>
                        coins
                    </div>

                    <div class="reward-bonus">
                        🔥 7-DAY BONUS
                        <strong>+50</strong>
                        extra coins
                    </div>

                </div>

                <p class="reward-streak" id="streakDisplay">
                    🔥 Login streak:
                    <strong><?php echo $currentStreak; ?> days</strong>
                </p>

            </div>

            <div class="reward-action">

                <button
                    type="button"
                    id="claimRewardBtn"
                    class="explore-button"
                    <?php echo $claimedToday ? 'disabled' : ''; ?>
                >
                    <?php echo $claimedToday
                        ? '✓ REWARD CLAIMED TODAY'
                        : '🎁 CLAIM DAILY REWARD'; ?>
                </button>

                <p id="rewardMessage">
                    <?php echo $claimedToday
                        ? 'Come back tomorrow for another reward!'
                        : 'Your daily treasure is waiting!'; ?>
                </p>

            </div>

        </div>

    </section>

    <!-- Quick links -->
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

    if (claimRewardBtn.disabled) {
        return;
    }

    claimRewardBtn.disabled = true;
    claimRewardBtn.textContent = "CLAIMING REWARD...";

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
            "🔥 Login streak: <strong>" +
            Number(data.streak) +
            " days</strong>";

        claimRewardBtn.textContent = "✓ REWARD CLAIMED";
        claimRewardBtn.disabled = true;

        // Refresh dashboard to display updated coin balance.
        setTimeout(function() {
            location.reload();
        }, 1200);

    } catch (error) {

        rewardMessage.textContent = "⚠️ " + error.message;

        claimRewardBtn.disabled = false;
        claimRewardBtn.textContent = "🎁 CLAIM DAILY REWARD";

    }

});
</script>

</body>
</html>
