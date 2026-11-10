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
<body>

<div class="container">

    <header style="display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">
        <div>
            <h2 style="color:#FFC629;">TREASURE HUNT</h2>
            <p>THE LOST ISLAND</p>
        </div>

        <a class="btn" href="logout.php">Logout</a>
    </header>

    <br><br>

    <section class="card">
        <p>WELCOME BACK, EXPLORER</p>

        <h1>
            <?php echo htmlspecialchars($player["username"]); ?>!
        </h1>

        <p>Your journey to discover the Lost Island continues.</p>

        <br>

        <a href="game/difficulty.php" class="btn">
    START EXPLORING
</a>
    </section>

    <br><br>

    <h2>Your Adventure Statistics</h2>

    <div class="stats">

        <div class="stat-card">
            <p>GOLD COINS</p>
            <h2><?php echo $player["coins"]; ?></h2>
        </div>

        <div class="stat-card">
            <p>TOTAL SCORE</p>
            <h2><?php echo $player["score"]; ?></h2>
        </div>

        <div class="stat-card">
            <p>ENERGY</p>
            <h2><?php echo $player["energy"]; ?>%</h2>
        </div>

        <div class="stat-card">
            <p>CURRENT LOCATION</p>
            <h2 style="font-size:20px;">
                <?php echo htmlspecialchars($player["current_location"]); ?>
            </h2>
        </div>

    </div>

    <section class="card">
        <h2>Your Adventure Awaits</h2>
        <p>Explore mysterious locations, solve puzzles and collect hidden treasures.</p>
    </section>
    
    <section class="card">
    <h2>🏆 Leaderboard</h2>
    <p>See how you rank against other explorers!</p>

    <a href="leaderboard.php" class="btn">
        VIEW LEADERBOARD
    </a>
</section>

</div>

</body>
</html>