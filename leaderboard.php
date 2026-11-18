<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit();
}

// Get top 10 players
$stmt = $pdo->query("
    SELECT username, score, coins
    FROM players
    ORDER BY score DESC, coins DESC
    LIMIT 10
");

$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Leaderboard | Treasure Hunt</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #101D38;
            color: #ffffff;
        }

        .leaderboard-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 25px;
        }

        .leaderboard-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .leaderboard-header .trophy {
            font-size: 55px;
            margin-bottom: 10px;
        }

        .leaderboard-header h1 {
            font-size: 38px;
            font-weight: 900;
            color: #FFC629;
            margin-bottom: 10px;
        }

        .leaderboard-header p {
            color: #AAB8D0;
            font-size: 16px;
        }

        .leaderboard-card {
            background: #1A2B49;
            border: 1px solid #314565;
            border-radius: 22px;
            padding: 25px;
            overflow: hidden;
        }

        .leaderboard-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 10px;
        }

        .leaderboard-title h2 {
            font-size: 22px;
            margin: 0;
            color: #ffffff;
        }

        .top-badge {
            background: rgba(255, 198, 41, 0.12);
            color: #FFC629;
            border: 1px solid rgba(255, 198, 41, 0.3);
            border-radius: 30px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .leaderboard-table th {
            color: #91A3BF;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 14px;
            border-bottom: 1px solid #314565;
        }

        .leaderboard-table td {
            padding: 18px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 15px;
        }

        .leaderboard-table tbody tr {
            transition: 0.2s;
        }

        .leaderboard-table tbody tr:hover {
            background: rgba(255,255,255,0.04);
        }

        .leaderboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .rank {
            font-weight: 800;
            font-size: 17px;
            color: #AAB8D0;
        }

        .rank-1 {
            color: #FFC629;
            font-size: 22px;
        }

        .rank-2 {
            color: #C7D2E2;
            font-size: 21px;
        }

        .rank-3 {
            color: #CD8B58;
            font-size: 21px;
        }

        .explorer-name {
            font-weight: 700;
            color: #ffffff;
        }

        .score-value {
            color: #FFC629;
            font-weight: 800;
        }

        .coins-value {
            color: #65D6A0;
            font-weight: 700;
        }

        .first-place {
            background: rgba(255, 198, 41, 0.07);
        }

        .rank-label {
            display: inline-block;
            margin-left: 8px;
            font-size: 11px;
            color: #FFC629;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #AAB8D0;
        }

        .empty-state .icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .back-button {
            display: inline-block;
            margin-top: 25px;
            padding: 13px 22px;
            background: #263A5D;
            color: #ffffff;
            border: 1px solid #3B5275;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .back-button:hover {
            background: #344D74;
            transform: translateY(-2px);
        }

        .footer-text {
            text-align: center;
            color: #7185A4;
            margin-top: 25px;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .leaderboard-container {
                margin: 20px auto;
                padding: 12px;
            }

            .leaderboard-header h1 {
                font-size: 29px;
            }

            .leaderboard-card {
                padding: 15px;
                border-radius: 16px;
            }

            .leaderboard-table th,
            .leaderboard-table td {
                padding: 13px 8px;
                font-size: 13px;
            }

            .leaderboard-title h2 {
                font-size: 18px;
            }

            .top-badge {
                font-size: 11px;
                padding: 6px 10px;
            }

            .rank-label {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="leaderboard-container">

    <header class="leaderboard-header">

        <div class="trophy">🏆</div>

        <h1>Island Leaderboard</h1>

        <p>Who will become the ultimate treasure hunter?</p>

    </header>

    <section class="leaderboard-card">

        <div class="leaderboard-title">

            <h2>🏅 Top Explorers</h2>

            <span class="top-badge">TOP 10 PLAYERS</span>

        </div>

        <div class="table-wrapper">

            <table class="leaderboard-table">

                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Explorer</th>
                        <th>Score</th>
                        <th>Coins</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($players) > 0): ?>

                        <?php foreach ($players as $index => $player): ?>

                            <?php
                            $rank = $index + 1;

                            $rankClass = "";

                            if ($rank === 1) {
                                $rankClass = "rank-1";
                            } elseif ($rank === 2) {
                                $rankClass = "rank-2";
                            } elseif ($rank === 3) {
                                $rankClass = "rank-3";
                            }
                            ?>

                            <tr class="<?php echo $rank === 1 ? 'first-place' : ''; ?>">

                                <td>
                                    <span class="rank <?php echo $rankClass; ?>">

                                        <?php
                                        if ($rank === 1) {
                                            echo "🥇";
                                        } elseif ($rank === 2) {
                                            echo "🥈";
                                        } elseif ($rank === 3) {
                                            echo "🥉";
                                        } else {
                                            echo "#" . $rank;
                                        }
                                        ?>

                                    </span>
                                </td>

                                <td>
                                    <span class="explorer-name">
                                        <?php echo htmlspecialchars($player["username"]); ?>
                                    </span>

                                    <?php if ($rank === 1): ?>
                                        <span class="rank-label">CHAMPION</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="score-value">
                                        <?php echo (int)$player["score"]; ?> ⭐
                                    </span>
                                </td>

                                <td>
                                    <span class="coins-value">
                                        <?php echo (int)$player["coins"]; ?> 🪙
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4">

                                <div class="empty-state">

                                    <div class="icon">🏝️</div>

                                    <h3>No explorers yet</h3>

                                    <p>Be the first to start your treasure hunt!</p>

                                </div>

                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

    <div style="text-align:center;">

        <a href="dashboard.php" class="back-button">
            ← Back to Dashboard
        </a>

    </div>

    <p class="footer-text">
        Keep exploring, solving puzzles and collecting treasures!
    </p>

</div>

</body>
</html>