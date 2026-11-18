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

    <style>

        body {
            background:
                radial-gradient(circle at top, #3c5278, transparent 45%),
                #101D38;
            color: white;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .victory-container {
            max-width: 850px;
            margin: 50px auto;
            background: #263A5D;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            border: 2px solid #FFC629;
            box-shadow: 0 0 35px rgba(255,198,41,0.2);
        }

        .victory-icon {
            font-size: 90px;
            margin-bottom: 15px;
        }

        .victory-title {
            color: #FFC629;
            font-size: 42px;
            margin-bottom: 10px;
        }

        .victory-subtitle {
            color: #d4dced;
            font-size: 18px;
            line-height: 1.6;
        }

        .victory-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 35px 0;
        }

        .victory-stat {
            background: #101D38;
            padding: 25px;
            border-radius: 12px;
        }

        .victory-stat span {
            color: #aab6cc;
            display: block;
            margin-bottom: 10px;
        }

        .victory-stat strong {
            color: #FFC629;
            font-size: 28px;
        }

        .victory-message {
            background: rgba(255,198,41,0.1);
            border: 1px solid rgba(255,198,41,0.4);
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            line-height: 1.7;
        }

        .victory-buttons {
            margin-top: 30px;
        }

        .victory-buttons a {
            display: inline-block;
            margin: 8px;
            padding: 14px 25px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
        }

        .primary-button {
            background: #FFC629;
            color: #101D38;
        }

        .secondary-button {
            background: #101D38;
            color: white;
            border: 1px solid #526586;
        }

        @media (max-width: 600px) {

            .victory-container {
                padding: 25px 15px;
                margin: 15px auto;
            }

            .victory-title {
                font-size: 30px;
            }

            .victory-icon {
                font-size: 65px;
            }

            .victory-stats {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<div class="victory-container">

    <div class="victory-icon">🏆</div>

    <h1 class="victory-title">VICTORY!</h1>

    <p class="victory-subtitle">
        Congratulations,
        <?php echo htmlspecialchars($player["username"]); ?>!
    </p>

    <p class="victory-subtitle">
        You conquered the Lost Island and discovered
        its greatest treasure!
    </p>

    <div class="victory-stats">

        <div class="victory-stat">
            <span>🏆 Final Score</span>
            <strong>
                <?php echo (int)$completion["final_score"]; ?>
            </strong>
        </div>

        <div class="victory-stat">
            <span>🪙 Coins Earned</span>
            <strong>
                <?php echo (int)$completion["coins_earned"]; ?>
            </strong>
        </div>

        <div class="victory-stat">
            <span>🧩 Puzzles Solved</span>
            <strong>
                <?php echo (int)$completion["puzzles_solved"]; ?>
            </strong>
        </div>

        <div class="victory-stat">
            <span>🏝️ Islands Completed</span>
            <strong>
                <?php echo (int)$completion["islands_completed"]; ?>/3
            </strong>
        </div>

        <div class="victory-stat">
            <span>🏅 Achievements Unlocked</span>
            <strong>
                <?php echo $totalAchievements; ?>
            </strong>
        </div>

        <div class="victory-stat">
            <span>📅 Completion Date</span>
            <strong style="font-size:18px;">
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

        🎉 <strong>Legendary Explorer!</strong>

        <p>
            Your courage, intelligence, and determination
            helped you conquer every island.
        </p>

        <p>
            The treasure is yours. Your adventure
            will be remembered!
        </p>

    </div>

    <div class="victory-buttons">

        <a href="dashboard.php" class="primary-button">
            Return to Dashboard
        </a>

        <a href="game/island.php" class="secondary-button">
            Explore Islands
        </a>

    </div>

</div>

</body>
</html>