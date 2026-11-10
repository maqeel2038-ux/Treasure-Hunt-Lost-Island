
<?php
session_start();
require_once "../config/database.php";

// Check login
if (!isset($_SESSION["player_id"])) {
    header("Location: ../login.php");
    exit();
}

// Get player information
$stmt = $pdo->prepare(
    "SELECT username, coins, score, energy, current_location
     FROM players
     WHERE player_id = ?"
);

$stmt->execute([$_SESSION["player_id"]]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Get player score
$score = (int)$player["score"];
$difficulty = $_SESSION["difficulty"] ?? "Easy";

// Unlock conditions
$jungleUnlocked = $score >= 100;
$templeUnlocked = $score >= 250;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Island Map | Treasure Hunt</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .location-card {
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .locked {
            opacity: 0.65;
        }

        .unlocked {
            border: 2px solid #FFC629;
        }

        .location-status {
            font-weight: bold;
            margin: 12px 0;
        }

        .locked-text {
            color: #ff7777;
        }

        .unlocked-text {
            color: #62df91;
        }

        .requirement {
            color: #cccccc;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="container">

    <header style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:#FFC629;">TREASURE HUNT</h2>
            <p>THE LOST ISLAND</p>
        </div>

        <a href="../dashboard.php" class="btn">Dashboard</a>
    </header>

    <br><br>

    <h1>Explore the Lost Island</h1>

    <p>
    Selected Difficulty:
    <strong><?php echo htmlspecialchars($difficulty); ?></strong>
</p>

    <p>
        Welcome, <?php echo htmlspecialchars($player["username"]); ?>.
        Choose your destination, explorer!
    </p>

    <br>

    <div class="stats">

        <div class="stat-card">
            <p>GOLD COINS</p>
            <h2><?php echo (int)$player["coins"]; ?></h2>
        </div>

        <div class="stat-card">
            <p>ENERGY</p>
            <h2><?php echo (int)$player["energy"]; ?>%</h2>
        </div>

        <div class="stat-card">
            <p>TOTAL SCORE</p>
            <h2><?php echo $score; ?></h2>
        </div>

    </div>

    <br>

    <!-- BEACH -->
    <div class="card location-card unlocked">

        <h2>🏝️ The Explorer's Beach</h2>

        <p>
            Your journey begins here. Explore the beach
            and solve your first set of challenges.
        </p>

        <p class="location-status unlocked-text">
            🔓 AVAILABLE
        </p>

        <a href="challenge.php?location=Beach" class="btn">
            Explore Beach
        </a>

    </div>

    <!-- HIDDEN JUNGLE -->
    <div class="card location-card <?php echo $jungleUnlocked ? 'unlocked' : 'locked'; ?>">

        <h2>🌴 Hidden Jungle</h2>

        <p>
            A mysterious jungle filled with ancient secrets
            and hidden treasures.
        </p>

        <?php if ($jungleUnlocked): ?>

            <p class="location-status unlocked-text">
                🔓 UNLOCKED
            </p>

            <a href="challenge.php?location=HiddenJungle" class="btn">
                Explore Hidden Jungle
            </a>

        <?php else: ?>

            <p class="location-status locked-text">
                🔒 LOCKED
            </p>

            <p class="requirement">
                Requirement: Earn 100 points.
                Current score: <?php echo $score; ?>
            </p>

        <?php endif; ?>

    </div>

    <!-- LOST TEMPLE -->
    <div class="card location-card <?php echo $templeUnlocked ? 'unlocked' : 'locked'; ?>">

        <h2>🏛️ Lost Temple</h2>

        <p>
            An ancient temple hiding the island's
            greatest treasure.
        </p>

        <?php if ($templeUnlocked): ?>

            <p class="location-status unlocked-text">
                🔓 UNLOCKED
            </p>

            <a href="challenge.php?location=LostTemple" class="btn">
                Explore Lost Temple
            </a>

        <?php else: ?>

            <p class="location-status locked-text">
                🔒 LOCKED
            </p>

            <p class="requirement">
                Requirement: Earn 250 points.
                Current score: <?php echo $score; ?>
            </p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
