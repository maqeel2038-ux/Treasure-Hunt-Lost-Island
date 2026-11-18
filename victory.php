<?php
session_start();
require_once "config/database.php";

// Check login
if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit();
}

$playerId = (int)$_SESSION["player_id"];

// Get player information
$stmt = $pdo->prepare("
    SELECT username
    FROM players
    WHERE player_id = ?
");

$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get the latest game completion
$stmt = $pdo->prepare("
    SELECT *
    FROM game_completions
    WHERE player_id = ?
    ORDER BY completion_id DESC
    LIMIT 1
");

$stmt->execute([$playerId]);
$completion = $stmt->fetch(PDO::FETCH_ASSOC);

// Prevent access if the game has not been completed
if (!$completion) {
    header("Location: game/island.php");
    exit();
}

// Get total achievements unlocked
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM player_achievements
    WHERE player_id = ?
");

$stmt->execute([$playerId]);
$totalAchievements = (int)$stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Victory | Treasure Hunt</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px 20px;
            font-family: 'Outfit', sans-serif;
            color: #ffffff;
            background:
                radial-gradient(circle at 50% 10%, rgba(255,198,41,0.12), transparent 35%),
                radial-gradient(circle at 10% 80%, rgba(67,110,180,0.15), transparent 35%),
                #101D38;
            overflow-x: hidden;
        }

        /* Background particles */

        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: #FFC629;
            border-radius: 50%;
            opacity: 0.5;
            box-shadow: 0 0 12px #FFC629;
            animation: float 6s infinite ease-in-out;
        }

        .particle:nth-child(1) {
            top: 15%;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            top: 25%;
            left: 85%;
            animation-delay: 1s;
        }

        .particle:nth-child(3) {
            top: 70%;
            left: 15%;
            animation-delay: 2s;
        }

        .particle:nth-child(4) {
            top: 80%;
            left: 90%;
            animation-delay: 1.5s;
        }

        .particle:nth-child(5) {
            top: 45%;
            left: 5%;
            animation-delay: 3s;
        }

        .particle:nth-child(6) {
            top: 55%;
            left: 95%;
            animation-delay: 2.5s;
        }

        .particle:nth-child(7) {
            top: 10%;
            left: 55%;
            animation-delay: 4s;
        }

        .particle:nth-child(8) {
            top: 90%;
            left: 50%;
            animation-delay: 1s;
        }

        @keyframes float {

            0%, 100% {
                transform: translateY(0);
                opacity: 0.2;
            }

            50% {
                transform: translateY(-25px);
                opacity: 0.9;
            }

        }

        /* Main container */

        .victory-container {
            position: relative;
            z-index: 1;
            max-width: 950px;
            margin: 30px auto;
            padding: 45px 40px;
            text-align: center;

            background: rgba(38,58,93,0.75);
            backdrop-filter: blur(15px);

            border: 1px solid rgba(255,198,41,0.35);
            border-radius: 28px;

            box-shadow:
                0 25px 80px rgba(0,0,0,0.35),
                0 0 35px rgba(255,198,41,0.08);
        }

        /* Trophy */

        .trophy-wrapper {
            width: 145px;
            height: 145px;
            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: radial-gradient(
                circle,
                rgba(255,198,41,0.22),
                rgba(255,198,41,0.03)
            );

            border: 1px solid rgba(255,198,41,0.4);
            border-radius: 50%;

            animation: trophyGlow 3s infinite ease-in-out;
        }

        .victory-icon {
            font-size: 85px;
            animation: trophyBounce 2s infinite ease-in-out;
        }

        @keyframes trophyBounce {

            0%, 100% {
                transform: translateY(0) rotate(-5deg);
            }

            50% {
                transform: translateY(-12px) rotate(5deg);
            }

        }

        @keyframes trophyGlow {

            0%, 100% {
                box-shadow: 0 0 15px rgba(255,198,41,0.1);
            }

            50% {
                box-shadow: 0 0 40px rgba(255,198,41,0.3);
            }

        }

        /* Heading */

        .victory-label {
            color: #FFC629;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .victory-title {
            margin: 0;
            font-size: clamp(42px, 7vw, 76px);
            font-weight: 900;
            letter-spacing: 3px;

            background: linear-gradient(
                180deg,
                #fff7d6,
                #FFC629,
                #e6a900
            );

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;

            filter: drop-shadow(0 5px 15px rgba(255,198,41,0.2));
        }

        .victory-subtitle {
            color: #cbd6e8;
            font-size: 17px;
            line-height: 1.8;
            margin: 12px 0;
        }

        .player-name {
            color: #FFC629;
            font-weight: 800;
        }

        /* Stats */

        .victory-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 38px 0;
        }

        .victory-stat {
            position: relative;
            padding: 24px 15px;

            background: rgba(16,29,56,0.8);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px;

            transition: 0.3s ease;
        }

        .victory-stat:hover {
            transform: translateY(-5px);
            border-color: rgba(255,198,41,0.45);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 12px;
        }

        .victory-stat span {
            display: block;
            color: #91a3c1;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .victory-stat strong {
            display: block;
            color: #FFC629;
            font-size: 30px;
            font-weight: 800;
        }

        .stat-date {
            font-size: 19px !important;
        }

        /* Victory message */

        .victory-message {
            position: relative;
            padding: 25px;
            margin: 30px 0;

            background: linear-gradient(
                135deg,
                rgba(255,198,41,0.10),
                rgba(255,198,41,0.03)
            );

            border: 1px solid rgba(255,198,41,0.3);
            border-radius: 18px;
        }

        .victory-message h3 {
            color: #FFC629;
            font-size: 22px;
            margin: 0 0 12px;
        }

        .victory-message p {
            color: #d2dced;
            font-size: 15px;
            line-height: 1.8;
            margin: 8px 0;
        }

        /* Buttons */

        .victory-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 32px;
        }

        .victory-buttons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 15px 28px;
            min-width: 210px;

            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 15px;

            transition: 0.25s ease;
        }

        .primary-button {
            color: #101D38;
            background: linear-gradient(135deg, #FFD84D, #FFC629);
            box-shadow: 0 5px 20px rgba(255,198,41,0.2);
        }

        .primary-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(255,198,41,0.35);
        }

        .secondary-button {
            color: #ffffff;
            background: rgba(16,29,56,0.7);
            border: 1px solid #526586;
        }

        .secondary-button:hover {
            transform: translateY(-3px);
            background: #344b70;
            border-color: #FFC629;
        }

        .footer-text {
            margin-top: 30px;
            color: #7185a6;
            font-size: 12px;
            letter-spacing: 1px;
        }

        /* Responsive */

        @media (max-width: 700px) {

            body {
                padding: 12px;
            }

            .victory-container {
                margin: 10px auto;
                padding: 30px 18px;
                border-radius: 20px;
            }

            .victory-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .victory-stat {
                padding: 20px 10px;
            }

            .victory-stat strong {
                font-size: 25px;
            }

            .trophy-wrapper {
                width: 115px;
                height: 115px;
            }

            .victory-icon {
                font-size: 68px;
            }

            .victory-subtitle {
                font-size: 15px;
            }

            .victory-buttons {
                flex-direction: column;
            }

            .victory-buttons a {
                width: 100%;
            }

        }

        @media (max-width: 380px) {

            .victory-stats {
                grid-template-columns: 1fr;
            }

        }

        /* Accessibility */

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

<body>

    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="victory-container">

        <div class="trophy-wrapper">
            <div class="victory-icon">🏆</div>
        </div>

        <div class="victory-label">
            Legendary Achievement Unlocked
        </div>

        <h1 class="victory-title">
            VICTORY!
        </h1>

        <p class="victory-subtitle">
            Congratulations,
            <span class="player-name">
                <?php echo htmlspecialchars($player["username"]); ?>
            </span>
        </p>

        <p class="victory-subtitle">
            You conquered every island and uncovered
            the legendary treasure!
        </p>

        <div class="victory-stats">

            <div class="victory-stat">
                <div class="stat-icon">🏆</div>
                <span>Final Score</span>
                <strong>
                    <?php echo number_format((int)$completion["final_score"]); ?>
                </strong>
            </div>

            <div class="victory-stat">
                <div class="stat-icon">🪙</div>
                <span>Coins Earned (Final Puzzle)</span>
                <strong>
                    <?php echo number_format((int)$completion["coins_earned"]); ?>
                </strong>
            </div>

            <div class="victory-stat">
                <div class="stat-icon">🧩</div>
                <span>Puzzles Solved</span>
                <strong>
                    <?php echo (int)$completion["puzzles_solved"]; ?>
                </strong>
            </div>

            <div class="victory-stat">
                <div class="stat-icon">🏝️</div>
                <span>Islands Completed</span>
                <strong>
                    <?php echo (int)$completion["islands_completed"]; ?>/3
                </strong>
            </div>

            <div class="victory-stat">
                <div class="stat-icon">🏅</div>
                <span>Achievements Unlocked</span>
                <strong>
                    <?php echo $totalAchievements; ?>
                </strong>
            </div>

            <div class="victory-stat">
                <div class="stat-icon">📅</div>
                <span>Victory Date</span>
                <strong class="stat-date">
                    <?php
                    echo date(
                        "d M Y",
                        strtotime($completion["completed_at"])
                    );
                    ?>
                </strong>
            </div>

        </div>

        <div class="victory-message">

            <h3>✨ Legendary Explorer! ✨</h3>

            <p>
                Your courage, intelligence, and determination
                helped you conquer every island.
            </p>

            <p>
                The treasure is yours. Your adventure
                will be remembered forever!
            </p>

        </div>

        <div class="victory-buttons">

            <a href="dashboard.php" class="primary-button">
                🏠 Return to Dashboard
            </a>

            <a href="game/island.php" class="secondary-button">
                🗺️ Explore Islands
            </a>

        </div>

        <div class="footer-text">
            TREASURE HUNT: THE LOST ISLAND
        </div>

    </div>

</body>
</html>