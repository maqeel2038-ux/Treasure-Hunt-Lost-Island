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
</head>

<body>

<div class="container">

    <h1 style="color:#FFC629;">🏆 Island Leaderboard</h1>

    <p>Top explorers competing for the greatest treasure!</p>

    <section class="card">

        <table style="width:100%; text-align:center; border-collapse:collapse;">

            <tr>
                <th>Rank</th>
                <th>Explorer</th>
                <th>Score</th>
                <th>Coins</th>
            </tr>

            <?php if (count($players) > 0): ?>

                <?php foreach ($players as $index => $player): ?>

                    <tr>
                        <td><?php echo $index + 1; ?></td>

                        <td>
                            <?php echo htmlspecialchars($player["username"]); ?>
                        </td>

                        <td><?php echo (int)$player["score"]; ?></td>

                        <td><?php echo (int)$player["coins"]; ?> 🪙</td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4">No explorers yet.</td>
                </tr>

            <?php endif; ?>

        </table>

    </section>

    <br>

    <a href="dashboard.php" class="btn">Back to Dashboard</a>

</div>

</body>
</html>