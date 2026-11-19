
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

    <title>Island Explorer | Treasure Hunt</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background:
                radial-gradient(circle at 15% 10%, rgba(36, 93, 142, 0.18), transparent 35%),
                radial-gradient(circle at 90% 80%, rgba(255, 198, 41, 0.07), transparent 30%),
                #0b1426;
            color: #f1f5f9;
            min-height: 100vh;
        }

        .island-container {
            width: 92%;
            max-width: 1250px;
            margin: auto;
            padding: 30px 0 60px;
        }

        /* NAVBAR */

        .island-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 18px 25px;
            background: rgba(20, 35, 59, 0.85);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px;
            backdrop-filter: blur(12px);
        }

        .brand h2 {
            color: #FFC629;
            margin: 0;
            font-size: 22px;
            letter-spacing: 2px;
        }

        .brand p {
            margin: 3px 0 0;
            color: #91a4bd;
            font-size: 12px;
            letter-spacing: 3px;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-btn {
            text-decoration: none;
            color: #dbe5f2;
            padding: 10px 17px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255,198,41,0.12);
            border-color: #FFC629;
            color: #FFC629;
        }

        /* HERO */

        .hero {
            text-align: center;
            padding: 55px 15px 35px;
        }

        .hero-label {
            color: #FFC629;
            font-size: 12px;
            letter-spacing: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero h1 {
            font-size: clamp(32px, 5vw, 54px);
            margin: 14px 0;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #FFC629;
        }

        .hero p {
            color: #9aabc2;
            font-size: 16px;
            line-height: 1.7;
            max-width: 600px;
            margin: 0 auto;
        }

        .difficulty-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 10px 18px;
            background: rgba(255,198,41,0.09);
            border: 1px solid rgba(255,198,41,0.25);
            border-radius: 30px;
            color: #FFC629;
            font-size: 13px;
            font-weight: 600;
        }

        /* STATS */

        .player-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }

        .stat-box {
            background: rgba(22, 37, 61, 0.8);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s, border-color 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-4px);
            border-color: rgba(255,198,41,0.3);
        }

        .stat-icon {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #8fa3bd;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 800;
            margin-top: 7px;
            color: #ffffff;
        }

        /* SECTION */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 10px;
        }

        .section-heading h2 {
            font-size: 23px;
            margin: 0;
        }

        .section-heading p {
            color: #8498b2;
            margin: 5px 0 0;
            font-size: 13px;
        }

        .island-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        /* ISLAND CARDS */

        .island-card {
            position: relative;
            overflow: hidden;
            background: #172740;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 22px;
            transition: transform 0.35s, border-color 0.35s, box-shadow 0.35s;
        }

        .island-card:hover {
            transform: translateY(-8px);
        }

        .island-card.available {
            border-color: rgba(255,198,41,0.35);
        }

        .island-card.available:hover {
            box-shadow: 0 15px 40px rgba(255,198,41,0.1);
            border-color: #FFC629;
        }

        .island-card.locked {
            opacity: 0.75;
        }

        .island-art {
            height: 210px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .beach-art {
            background:
                linear-gradient(180deg, rgba(12,25,42,0.05), rgba(12,25,42,0.65)),
                linear-gradient(135deg, #1b8294, #54b6bd 45%, #e0b66c);
        }

        .jungle-art {
            background:
                linear-gradient(180deg, rgba(12,25,42,0.05), rgba(12,25,42,0.7)),
                linear-gradient(135deg, #153e31, #2f8052 45%, #8a9e47);
        }

        .temple-art {
            background:
                linear-gradient(180deg, rgba(12,25,42,0.05), rgba(12,25,42,0.75)),
                linear-gradient(135deg, #382b49, #8b6849 50%, #d4a34b);
        }

        .island-emoji {
            font-size: 100px;
            filter: drop-shadow(0 12px 15px rgba(0,0,0,0.3));
            transition: transform 0.4s;
            z-index: 1;
        }

        .island-card:hover .island-emoji {
            transform: scale(1.12);
        }

        .art-overlay {
            position: absolute;
            bottom: 15px;
            left: 18px;
            color: white;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 2;
        }

        .island-number {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 7px 11px;
            border-radius: 8px;
            background: rgba(10,20,35,0.6);
            color: white;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            z-index: 2;
        }

        .badge-unlocked {
            background: rgba(40, 180, 110, 0.18);
            color: #79f0ad;
            border: 1px solid rgba(121,240,173,0.3);
        }

        .badge-locked {
            background: rgba(10,20,35,0.7);
            color: #d3d8e0;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .island-content {
            padding: 23px;
        }

        .island-content h3 {
            font-size: 21px;
            margin: 0 0 10px;
            color: #f5f7fb;
        }

        .island-content p {
            color: #9aabc2;
            font-size: 13px;
            line-height: 1.7;
            min-height: 65px;
            margin: 0 0 18px;
        }

        .requirement-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 13px;
            margin-bottom: 18px;
        }

        .requirement-title {
            font-size: 11px;
            color: #8fa3bd;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .requirement-value {
            font-size: 14px;
            color: #f1f5f9;
            font-weight: 600;
        }

        .progress-track {
            height: 6px;
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #d99c13, #FFC629);
            border-radius: 20px;
            transition: width 0.5s;
        }

        .explore-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            width: 100%;
            padding: 13px;
            background: #FFC629;
            color: #142033;
            font-weight: 800;
            font-size: 14px;
            border-radius: 11px;
            transition: 0.3s;
        }

        .explore-btn:hover {
            background: #ffda69;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,198,41,0.2);
        }

        .locked-btn {
            display: block;
            text-align: center;
            width: 100%;
            padding: 13px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            color: #8391a5;
            font-weight: 700;
            font-size: 14px;
            border-radius: 11px;
            cursor: not-allowed;
        }

        /* FOOTER */

        .island-footer {
            text-align: center;
            margin-top: 45px;
            padding: 22px;
            color: #62758e;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {
            .island-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .island-card:last-child {
                grid-column: span 2;
                max-width: 500px;
                width: 100%;
                justify-self: center;
            }
        }

        @media (max-width: 600px) {
            .island-container {
                width: 94%;
                padding-top: 15px;
            }

            .island-nav {
                padding: 15px;
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-actions {
                width: 100%;
            }

            .nav-btn {
                flex: 1;
                text-align: center;
            }

            .hero {
                padding: 40px 5px 25px;
            }

            .hero h1 {
                font-size: 34px;
            }

            .player-stats {
                gap: 8px;
            }

            .stat-box {
                padding: 14px 8px;
            }

            .stat-value {
                font-size: 21px;
            }

            .stat-label {
                font-size: 9px;
                letter-spacing: 1px;
            }

            .island-grid {
                grid-template-columns: 1fr;
            }

            .island-card:last-child {
                grid-column: auto;
                max-width: none;
            }

            .island-art {
                height: 180px;
            }

            .island-emoji {
                font-size: 85px;
            }

            .section-heading h2 {
                font-size: 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="island-container">

    <!-- NAVBAR -->
    <header class="island-nav">

        <div class="brand">
            <h2>TREASURE HUNT</h2>
            <p>THE LOST ISLAND</p>
        </div>

        <div class="nav-actions">
            <a href="../leaderboard.php" class="nav-btn">🏆 Leaderboard</a>
            <a href="../dashboard.php" class="nav-btn">⌂ Dashboard</a>
        </div>

    </header>

    <!-- HERO -->
    <section class="hero">

        <div class="hero-label">Your Adventure Awaits</div>

        <h1>Explore the <span>Lost Island</span></h1>

        <p>
            Discover mysterious locations, solve puzzles, collect treasures
            and uncover the secrets hidden across the island.
        </p>

        <div class="difficulty-badge">
            ⚔️ Current Difficulty: <?php echo htmlspecialchars($difficulty); ?>
        </div>

    </section>

    <!-- PLAYER STATS -->
    <section class="player-stats">

        <div class="stat-box">
            <div class="stat-icon">🪙</div>
            <div class="stat-label">Gold Coins</div>
            <div class="stat-value">
                <?php echo (int)$player["coins"]; ?>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon">⚡</div>
            <div class="stat-label">Energy</div>
            <div class="stat-value">
                <?php echo (int)$player["energy"]; ?>%
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon">⭐</div>
            <div class="stat-label">Total Score</div>
            <div class="stat-value">
                <?php echo $score; ?>
            </div>
        </div>

    </section>

    <!-- ISLANDS -->
    <section>

        <div class="section-heading">
            <div>
                <h2>🗺️ Choose Your Destination</h2>
                <p>Explore available locations and unlock new adventures.</p>
            </div>
        </div>

        <div class="island-grid">

            <!-- BEACH -->
            <div class="island-card available">

                <div class="island-art beach-art">

                    <span class="island-number">AREA 01</span>

                    <span class="status-badge badge-unlocked">
                        UNLOCKED
                    </span>

                    <div class="island-emoji">🏝️</div>

                    <div class="art-overlay">
                        Starting Zone
                    </div>

                </div>

                <div class="island-content">

                    <h3>Explorer's Beach</h3>

                    <p>
                        Your adventure begins on a beautiful tropical beach.
                        Search the shore, solve puzzles and discover your first treasures.
                    </p>

                    <div class="requirement-box">

                        <div class="requirement-title">
                            Access Status
                        </div>

                        <div class="requirement-value">
                            ✦ Available from the beginning
                        </div>

                    </div>

                    <a href="difficulty.php?location=Beach"
                       class="explore-btn">
                        🏖️ Explore Beach
                    </a>

                </div>

            </div>

            <!-- HIDDEN JUNGLE -->
            <div class="island-card <?php echo $jungleUnlocked ? 'available' : 'locked'; ?>">

                <div class="island-art jungle-art">

                    <span class="island-number">AREA 02</span>

                    <span class="status-badge <?php echo $jungleUnlocked ? 'badge-unlocked' : 'badge-locked'; ?>">
                        <?php echo $jungleUnlocked ? 'UNLOCKED' : 'LOCKED'; ?>
                    </span>

                    <div class="island-emoji">
                        <?php echo $jungleUnlocked ? '🌴' : '🌳'; ?>
                    </div>

                    <div class="art-overlay">
                        The Wild Territory
                    </div>

                </div>

                <div class="island-content">

                    <h3>Hidden Jungle</h3>

                    <p>
                        Venture deep into a mysterious jungle filled with
                        ancient secrets, dangerous challenges and hidden treasures.
                    </p>

                    <div class="requirement-box">

                        <div class="requirement-title">
                            Unlock Requirement
                        </div>

                        <div class="requirement-value">
                            ⭐ <?php echo $score; ?> / 100 Points
                        </div>

                        <div class="progress-track">

                            <div class="progress-fill"
                                 style="width: <?php echo min(100, ($score / 100) * 100); ?>%;">
                            </div>

                        </div>

                    </div>

                    <?php if ($jungleUnlocked): ?>

                        <a href="difficulty.php?location=HiddenJungle"
                           class="explore-btn">
                            🌴 Explore Jungle
                        </a>

                    <?php else: ?>

                        <button class="locked-btn" disabled>
                            🔒 Earn 100 Points to Unlock
                        </button>

                    <?php endif; ?>

                </div>

            </div>

            <!-- LOST TEMPLE -->
            <div class="island-card <?php echo $templeUnlocked ? 'available' : 'locked'; ?>">

                <div class="island-art temple-art">

                    <span class="island-number">AREA 03</span>

                    <span class="status-badge <?php echo $templeUnlocked ? 'badge-unlocked' : 'badge-locked'; ?>">
                        <?php echo $templeUnlocked ? 'UNLOCKED' : 'LOCKED'; ?>
                    </span>

                    <div class="island-emoji">
                        <?php echo $templeUnlocked ? '🏛️' : '🗿'; ?>
                    </div>

                    <div class="art-overlay">
                        The Ancient Ruins
                    </div>

                </div>

                <div class="island-content">

                    <h3>Lost Temple</h3>

                    <p>
                        Enter the forgotten ruins and face the island's
                        toughest puzzles to uncover its greatest treasure.
                    </p>

                    <div class="requirement-box">

                        <div class="requirement-title">
                            Unlock Requirement
                        </div>

                        <div class="requirement-value">
                            ⭐ <?php echo $score; ?> / 250 Points
                        </div>

                        <div class="progress-track">

                            <div class="progress-fill"
                                 style="width: <?php echo min(100, ($score / 250) * 100); ?>%;">
                            </div>

                        </div>

                    </div>

                    <?php if ($templeUnlocked): ?>

                        <a href="difficulty.php?location=LostTemple"
                           class="explore-btn">
                            🏛️ Explore Temple
                        </a>

                    <?php else: ?>

                        <button class="locked-btn" disabled>
                            🔒 Earn 250 Points to Unlock
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="island-footer">
        Treasure Hunt: The Lost Island &nbsp;•&nbsp;
        Explore. Solve. Discover.
    </footer>

</div>

</body>
</html>
