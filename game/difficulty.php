
<?php
session_start();

if (!isset($_SESSION["player_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $difficulty = $_POST["difficulty"] ?? "";

    $allowed = ["Easy", "Medium", "Hard"];

    if (in_array($difficulty, $allowed, true)) {
        $_SESSION["difficulty"] = $difficulty;

        header("Location: island.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Select Difficulty | Treasure Hunt</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(43, 83, 130, 0.2), transparent 40%),
                #0b1426;
            color: #f1f5f9;
        }

        .difficulty-container {
            width: 92%;
            max-width: 1100px;
            margin: auto;
            padding: 35px 0 60px;
        }

        /* NAVBAR */

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            background: rgba(20, 35, 59, 0.85);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
        }

        .brand h2 {
            color: #FFC629;
            font-size: 21px;
            letter-spacing: 2px;
            margin: 0;
        }

        .brand p {
            color: #91a4bd;
            font-size: 11px;
            letter-spacing: 3px;
            margin: 4px 0 0;
        }

        .back-btn {
            text-decoration: none;
            color: #dbe5f2;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 10px 17px;
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-btn:hover {
            border-color: #FFC629;
            color: #FFC629;
        }

        /* HERO */

        .difficulty-hero {
            text-align: center;
            padding: 65px 15px 40px;
        }

        .hero-label {
            color: #FFC629;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .difficulty-hero h1 {
            font-size: clamp(34px, 5vw, 52px);
            font-weight: 900;
            margin: 15px 0;
        }

        .difficulty-hero h1 span {
            color: #FFC629;
        }

        .difficulty-hero p {
            color: #94a6bf;
            font-size: 15px;
            line-height: 1.7;
            max-width: 600px;
            margin: auto;
        }

        /* CARDS */

        .difficulty-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .difficulty-card {
            position: relative;
            display: flex;
            flex-direction: column;
            text-align: left;
            padding: 27px;
            border-radius: 20px;
            background: #172740;
            border: 1px solid rgba(255,255,255,0.09);
            color: #f1f5f9;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
        }

        .difficulty-card:hover {
            transform: translateY(-8px);
        }

        .difficulty-card:focus-visible {
            outline: 3px solid #FFC629;
            outline-offset: 4px;
        }

        .easy-card:hover {
            border-color: #55df9a;
            box-shadow: 0 15px 35px rgba(85,223,154,0.08);
        }

        .medium-card:hover {
            border-color: #ffb84d;
            box-shadow: 0 15px 35px rgba(255,184,77,0.08);
        }

        .hard-card:hover {
            border-color: #ff6868;
            box-shadow: 0 15px 35px rgba(255,104,104,0.08);
        }

        .difficulty-icon {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            border-radius: 18px;
            margin-bottom: 22px;
        }

        .easy-card .difficulty-icon {
            background: rgba(85,223,154,0.12);
        }

        .medium-card .difficulty-icon {
            background: rgba(255,184,77,0.12);
        }

        .hard-card .difficulty-icon {
            background: rgba(255,104,104,0.12);
        }

        .difficulty-name {
            font-size: 25px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .difficulty-description {
            color: #94a6bf;
            font-size: 13px;
            line-height: 1.7;
            min-height: 65px;
            margin-bottom: 22px;
        }

        .difficulty-tag {
            display: inline-block;
            width: fit-content;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 23px;
        }

        .easy-tag {
            color: #65e8a7;
            background: rgba(85,223,154,0.1);
        }

        .medium-tag {
            color: #ffbd61;
            background: rgba(255,184,77,0.1);
        }

        .hard-tag {
            color: #ff7777;
            background: rgba(255,104,104,0.1);
        }

        /* DETAILS */

        .difficulty-details {
            border-top: 1px solid rgba(255,255,255,0.07);
            padding-top: 18px;
            margin-top: auto;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 13px;
            font-size: 13px;
        }

        .detail-label {
            color: #8fa3bd;
        }

        .detail-value {
            font-weight: 700;
            color: #f1f5f9;
        }

        .easy-card .detail-value.reward {
            color: #65e8a7;
        }

        .medium-card .detail-value.reward {
            color: #ffbd61;
        }

        .hard-card .detail-value.reward {
            color: #ff7777;
        }

        /* SELECT BUTTON */

        .select-btn {
            margin-top: 20px;
            padding: 14px;
            text-align: center;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 800;
            transition: 0.3s;
        }

        .easy-card .select-btn {
            background: rgba(85,223,154,0.12);
            color: #65e8a7;
            border: 1px solid rgba(85,223,154,0.2);
        }

        .medium-card .select-btn {
            background: rgba(255,184,77,0.12);
            color: #ffbd61;
            border: 1px solid rgba(255,184,77,0.2);
        }

        .hard-card .select-btn {
            background: rgba(255,104,104,0.12);
            color: #ff7777;
            border: 1px solid rgba(255,104,104,0.2);
        }

        .difficulty-card:hover .select-btn {
            color: #142033;
        }

        .easy-card:hover .select-btn {
            background: #55df9a;
        }

        .medium-card:hover .select-btn {
            background: #ffb84d;
        }

        .hard-card:hover .select-btn {
            background: #ff6868;
        }

        /* INFO */

        .info-panel {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid rgba(255,198,41,0.15);
            background: rgba(255,198,41,0.04);
            border-radius: 15px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .info-icon {
            font-size: 25px;
        }

        .info-panel p {
            color: #9aabc2;
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
        }

        .info-panel strong {
            color: #FFC629;
        }

        .footer {
            text-align: center;
            color: #62758e;
            font-size: 12px;
            margin-top: 40px;
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {
            .difficulty-grid {
                grid-template-columns: 1fr;
                max-width: 550px;
                margin: auto;
            }

            .difficulty-description {
                min-height: auto;
            }

            .difficulty-card {
                padding: 24px;
            }
        }

        @media (max-width: 500px) {
            .difficulty-container {
                width: 94%;
                padding-top: 15px;
            }

            .top-nav {
                padding: 15px;
            }

            .brand h2 {
                font-size: 17px;
            }

            .back-btn {
                font-size: 12px;
                padding: 9px 12px;
            }

            .difficulty-hero {
                padding: 45px 5px 30px;
            }

            .difficulty-hero h1 {
                font-size: 34px;
            }

            .info-panel {
                align-items: flex-start;
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

<div class="difficulty-container">

    <!-- NAVBAR -->
    <header class="top-nav">

        <div class="brand">
            <h2>TREASURE HUNT</h2>
            <p>THE LOST ISLAND</p>
        </div>

        <a href="../dashboard.php" class="back-btn">
            ← Dashboard
        </a>

    </header>

    <!-- HERO -->
    <section class="difficulty-hero">

        <div class="hero-label">Prepare Your Adventure</div>

        <h1>Choose Your <span>Challenge</span></h1>

        <p>
            Choose your difficulty wisely. The harder the challenge,
            the greater your treasure rewards!
        </p>

    </section>

    <!-- DIFFICULTY CARDS -->
    <form method="POST">

        <div class="difficulty-grid">

            <!-- EASY -->
            <button type="submit"
                    name="difficulty"
                    value="Easy"
                    class="difficulty-card easy-card">

                <div class="difficulty-icon">🧭</div>

                <div class="difficulty-name">Easy</div>

                <div class="difficulty-description">
                    A relaxed adventure for beginners.
                    Take your time and enjoy discovering the island.
                </div>

                <span class="difficulty-tag easy-tag">
                    BEGINNER FRIENDLY
                </span>

                <div class="difficulty-details">

                    <div class="detail-row">
                        <span class="detail-label">⏱️ Time Limit</span>
                        <span class="detail-value">60 Seconds</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">🪙 Coin Multiplier</span>
                        <span class="detail-value reward">1×</span>
                    </div>

                </div>

                <div class="select-btn">
                    Select Easy →
                </div>

            </button>

            <!-- MEDIUM -->
            <button type="submit"
                    name="difficulty"
                    value="Medium"
                    class="difficulty-card medium-card">

                <div class="difficulty-icon">⚔️</div>

                <div class="difficulty-name">Medium</div>

                <div class="difficulty-description">
                    A balanced challenge for experienced explorers.
                    Think quickly and earn bigger rewards.
                </div>

                <span class="difficulty-tag medium-tag">
                    EXPERIENCED EXPLORER
                </span>

                <div class="difficulty-details">

                    <div class="detail-row">
                        <span class="detail-label">⏱️ Time Limit</span>
                        <span class="detail-value">45 Seconds</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">🪙 Coin Multiplier</span>
                        <span class="detail-value reward">2×</span>
                    </div>

                </div>

                <div class="select-btn">
                    Select Medium →
                </div>

            </button>

            <!-- HARD -->
            <button type="submit"
                    name="difficulty"
                    value="Hard"
                    class="difficulty-card hard-card">

                <div class="difficulty-icon">🔥</div>

                <div class="difficulty-name">Hard</div>

                <div class="difficulty-description">
                    The ultimate test. Limited time, intense puzzles
                    and maximum rewards for skilled adventurers.
                </div>

                <span class="difficulty-tag hard-tag">
                    EXPERT CHALLENGE
                </span>

                <div class="difficulty-details">

                    <div class="detail-row">
                        <span class="detail-label">⏱️ Time Limit</span>
                        <span class="detail-value">30 Seconds</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">🪙 Coin Multiplier</span>
                        <span class="detail-value reward">3×</span>
                    </div>

                </div>

                <div class="select-btn">
                    Select Hard →
                </div>

            </button>

        </div>

    </form>

    <!-- INFO -->
    <div class="info-panel">

        <div class="info-icon">💡</div>

        <p>
            <strong>Pro Tip:</strong>
            Higher difficulties give you more coins for correct answers,
            but you have less time to solve each puzzle.
            Choose a difficulty that matches your skills!
        </p>

    </div>

    <footer class="footer">
        Treasure Hunt: The Lost Island &nbsp;•&nbsp;
        Explore. Solve. Discover.
    </footer>

</div>

</body>
</html>
