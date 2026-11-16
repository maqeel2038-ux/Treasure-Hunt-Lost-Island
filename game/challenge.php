
<?php
session_start();
require_once "../config/database.php";

// Check login
if (!isset($_SESSION["player_id"])) {
    header("Location: ../login.php");
    exit();
}

$player_id = $_SESSION["player_id"];

// Get player information
$stmt = $pdo->prepare(
    "SELECT username, coins, score, energy
     FROM players
     WHERE player_id = ?"
);
$stmt->execute([$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Island details
$location = $_GET["location"] ?? "Beach";

$challenges = [
    "Beach" => [
        "name" => "The Explorer's Beach",
        "difficulty" => "Beginner"
    ],
    "HiddenJungle" => [
        "name" => "Hidden Jungle",
        "difficulty" => "Intermediate"
    ],
    "LostTemple" => [
        "name" => "Lost Temple",
        "difficulty" => "Advanced"
    ]
];

// Validate island
if (!isset($challenges[$location])) {
    header("Location: island.php");
    exit();
}

$challenge = $challenges[$location];

// Check island unlocking requirements
$score = (int)$player["score"];

if ($location === "HiddenJungle" && $score < 100) {
    header("Location: island.php");
    exit();
}

if ($location === "LostTemple" && $score < 250) {
    header("Location: island.php");
    exit();
}

// Check previous completion
$stmt = $pdo->prepare(
    "SELECT id FROM player_challenges
     WHERE player_id = ? AND location = ?"
);
$stmt->execute([$player_id, $location]);

$alreadyCompleted = $stmt->fetch() !== false;

// Difficulty and time limit
$selectedDifficulty = $_SESSION["difficulty"] ?? "Easy";

$timeLimit = [
    "Easy" => 60,
    "Medium" => 45,
    "Hard" => 30
];

if (!isset($timeLimit[$selectedDifficulty])) {
    $selectedDifficulty = "Easy";
}

$gameTime = $timeLimit[$selectedDifficulty];

// Current session streak
$currentStreak = (int)($_SESSION["game_streak"] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($challenge["name"]); ?>
        | Treasure Hunt
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background:
                radial-gradient(circle at top right, #263f67, transparent 35%),
                #101d38;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
            min-height: 100vh;
        }

        .challenge-container {
            max-width: 850px;
            margin: 30px auto;
            background: #1e2d49;
            padding: 35px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .game-brand {
            color: #ffc629;
            letter-spacing: 2px;
            font-size: 13px;
            font-weight: bold;
        }

        h1 {
            color: #ffc629;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #aab6cc;
            margin-bottom: 25px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat {
            background: #263a5d;
            padding: 18px 10px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .stat strong {
            display: block;
            color: #ffc629;
            font-size: 22px;
            margin-top: 8px;
        }

        .game-info {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 25px 0;
        }

        .info-badge {
            background: #263a5d;
            padding: 10px 18px;
            border-radius: 20px;
            font-size: 13px;
        }

        .info-badge strong {
            color: #ffc629;
        }

        .game-card {
            background: #152440;
            border-radius: 15px;
            padding: 25px;
            margin-top: 25px;
        }

        /* Streak panel */
        .streak-panel {
            display: flex;
            justify-content: space-around;
            gap: 15px;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #46315f, #263a5d);
            border: 1px solid #ffc629;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
        }

        .streak-panel div {
            color: #ffffff;
            font-size: 14px;
        }

        .streak-panel strong {
            display: block;
            color: #ffc629;
            font-size: 24px;
            margin-top: 8px;
        }

        .timer-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: #aab6cc;
        }

        #timer {
            color: #ffc629;
            font-size: 40px;
            font-weight: bold;
            margin: 15px 0;
            font-variant-numeric: tabular-nums;
        }

        .timer-track {
            width: 100%;
            height: 9px;
            background: #344563;
            border-radius: 20px;
            overflow: hidden;
        }

        .timer-progress {
            width: 100%;
            height: 100%;
            background: #ffc629;
            transition: width 1s linear;
            border-radius: 20px;
        }

        .question {
            min-height: 230px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 25px 0;
            background: #203454;
            border-radius: 12px;
            padding: 15px;
        }

        .question img {
            max-width: 100%;
            width: 420px;
            max-height: 350px;
            object-fit: contain;
            border-radius: 10px;
            background: white;
        }

        .loading-text {
            color: #ffc629;
            font-size: 16px;
        }

        .answer-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #344563;
            border-radius: 10px;
            font-size: 17px;
            margin: 15px 0;
            text-align: center;
            outline: none;
        }

        .answer-input:focus {
            border-color: #ffc629;
        }

        .game-button {
            display: inline-block;
            background: #ffc629;
            color: #101d38;
            padding: 14px 25px;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin: 6px;
            transition: 0.2s;
        }

        .game-button:hover {
            transform: translateY(-2px);
            background: #ffdb70;
        }

        .game-button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        .retry-button {
            background: #3b82b5;
            color: white;
        }

        .message {
            padding: 16px;
            border-radius: 10px;
            margin: 20px 0;
            line-height: 1.6;
            display: none;
        }

        .success {
            display: block;
            background: #155d3b;
            border: 1px solid #299c62;
        }

        .error {
            display: block;
            background: #7b2525;
            border: 1px solid #b94b4b;
        }

        .links {
            margin-top: 25px;
        }

        .links a {
            display: inline-block;
            color: #ffc629;
            margin: 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .game-instructions {
            color: #aab6cc;
            font-size: 13px;
            margin-top: 20px;
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            .challenge-container {
                padding: 20px 15px;
                margin: 10px auto;
            }

            .stats {
                gap: 8px;
            }

            .stat {
                padding: 13px 5px;
                font-size: 12px;
            }

            .stat strong {
                font-size: 18px;
            }

            .game-card {
                padding: 15px;
            }

            .question {
                min-height: 180px;
            }

            #timer {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>

<div class="challenge-container">

    <div class="game-brand">
        🏝️ TREASURE HUNT: THE LOST ISLAND
    </div>

    <h1><?php echo htmlspecialchars($challenge["name"]); ?></h1>

    <p class="subtitle">
        Welcome back,
        <?php echo htmlspecialchars($player["username"]); ?>!
        Your next adventure awaits.
    </p>

    <div class="stats">

        <div class="stat">
            🪙 Coins
            <strong><?php echo (int)$player["coins"]; ?></strong>
        </div>

        <div class="stat">
            ⭐ Score
            <strong><?php echo (int)$player["score"]; ?></strong>
        </div>

        <div class="stat">
            ⚡ Energy
            <strong><?php echo (int)$player["energy"]; ?></strong>
        </div>

    </div>

    <div class="game-info">

        <div class="info-badge">
            Difficulty:
            <strong><?php echo htmlspecialchars($selectedDifficulty); ?></strong>
        </div>

        <div class="info-badge">
            Challenge:
            <strong><?php echo htmlspecialchars($challenge["difficulty"]); ?></strong>
        </div>

        <div class="info-badge">
            Time:
            <strong><?php echo $gameTime; ?> seconds</strong>
        </div>

    </div>

    <?php if ($alreadyCompleted): ?>

        <div class="message success">
            🏆 You have already completed this challenge!
            No additional rewards are available.
        </div>

        <div class="links">
            <a href="island.php">← Back to Island</a>
            <a href="../dashboard.php">Dashboard</a>
        </div>

    <?php else: ?>

        <div class="game-card">

            <h2>🧩 Solve the Banana Puzzle</h2>

            <p>
                Look carefully at the picture and enter the correct answer.
            </p>

            <!-- Streak system -->
            <div class="streak-panel">

                <div>
                    🔥 Winning Streak
                    <strong id="streakCount">
                        <?php echo $currentStreak; ?>
                    </strong>
                </div>

                <div>
                    🎁 Streak Bonus
                    <strong id="streakBonus">
                        <?php echo min($currentStreak * 5, 25); ?> coins
                    </strong>
                </div>

            </div>

            <div class="timer-heading">
                <span>TIME REMAINING</span>
                <span id="timerStatus">Ready</span>
            </div>

            <div id="timer">
                <?php echo $gameTime; ?>s
            </div>

            <div class="timer-track">
                <div class="timer-progress" id="timerProgress"></div>
            </div>

            <div class="question" id="question">
                <p class="loading-text">
                    Loading your Banana puzzle...
                </p>
            </div>

            <form id="challengeForm">

                <input
                    type="text"
                    id="answer"
                    class="answer-input"
                    placeholder="Enter your answer..."
                    autocomplete="off"
                    required
                    disabled
                >

                <button
                    type="submit"
                    id="submitBtn"
                    class="game-button"
                    disabled
                >
                    Submit Answer →
                </button>

            </form>

            <button
                type="button"
                id="retryBtn"
                class="game-button retry-button"
                style="display:none;"
            >
                🔄 Try Another Puzzle
            </button>

            <div id="message" class="message"></div>

            <p class="game-instructions">
                💡 Tip: Check the picture carefully before submitting.
                Your time starts when the puzzle image has loaded.
            </p>

        </div>

        <div class="links" id="links" style="display:none;">
            <a href="island.php">← Back to Island</a>
            <a href="../dashboard.php">Dashboard</a>
        </div>

    <?php endif; ?>

</div>

<?php if (!$alreadyCompleted): ?>

<script>
    const locationName = <?php echo json_encode($location); ?>;
    const difficulty = <?php echo json_encode($selectedDifficulty); ?>;
    const gameTime = <?php echo (int)$gameTime; ?>;

    const question = document.getElementById("question");
    const answerInput = document.getElementById("answer");
    const submitBtn = document.getElementById("submitBtn");
    const challengeForm = document.getElementById("challengeForm");
    const message = document.getElementById("message");
    const timer = document.getElementById("timer");
    const timerProgress = document.getElementById("timerProgress");
    const timerStatus = document.getElementById("timerStatus");
    const retryBtn = document.getElementById("retryBtn");
    const links = document.getElementById("links");

    const streakCount = document.getElementById("streakCount");
    const streakBonus = document.getElementById("streakBonus");

    let timeLeft = gameTime;
    let countdown = null;
    let gameFinished = false;
    let loading = false;
    let submitting = false;

    // Display messages
    function showMessage(text, type) {
        message.textContent = text;
        message.className = "message " + type;
    }

    function hideMessage() {
        message.textContent = "";
        message.className = "message";
    }

    // Reset timer
    function resetTimer() {
        clearInterval(countdown);

        timeLeft = gameTime;

        timer.textContent = timeLeft + "s";
        timer.style.color = "#ffc629";

        timerProgress.style.width = "100%";
        timerProgress.style.background = "#ffc629";

        timerStatus.textContent = "Ready";
    }

    // Start countdown
    function startTimer() {

        resetTimer();

        timerStatus.textContent = "Running";

        countdown = setInterval(function() {

            timeLeft--;

            timer.textContent = timeLeft + "s";

            const percentage = (timeLeft / gameTime) * 100;

            timerProgress.style.width = percentage + "%";

            if (timeLeft <= 10) {
                timer.style.color = "#ff5555";
                timerProgress.style.background = "#ff5555";
                timerStatus.textContent = "Hurry up!";
            }

            if (timeLeft <= 0) {

                clearInterval(countdown);

                gameFinished = true;

                answerInput.disabled = true;
                submitBtn.disabled = true;

                timerStatus.textContent = "Time is up!";

                showMessage(
                    "⏰ Time is up! You can try another puzzle.",
                    "error"
                );

                retryBtn.style.display = "inline-block";
            }

        }, 1000);
    }

    // Load puzzle
    async function loadPuzzle() {

        if (loading) return;

        loading = true;
        gameFinished = false;
        submitting = false;

        clearInterval(countdown);
        resetTimer();
        hideMessage();

        links.style.display = "none";
        retryBtn.style.display = "none";

        answerInput.value = "";
        answerInput.disabled = true;
        submitBtn.disabled = true;

        question.innerHTML =
            '<p class="loading-text">⏳ Connecting to Banana API...</p>';

        try {

            const response = await fetch(
                "../api/puzzle.php?location=" +
                encodeURIComponent(locationName),
                {
                    cache: "no-store"
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.error || "Could not load puzzle."
                );
            }

            if (!data.image) {
                throw new Error("Puzzle image was not received.");
            }

            question.innerHTML =
                '<p class="loading-text">🧩 Loading puzzle image...</p>';

            const image = new Image();

            image.alt = "Banana picture puzzle";

            image.onload = function() {

                if (gameFinished) return;

                question.replaceChildren(image);

                answerInput.disabled = false;
                submitBtn.disabled = false;

                answerInput.focus();

                startTimer();

                loading = false;
            };

            image.onerror = function() {

                question.innerHTML =
                    '<p class="loading-text">Puzzle image could not be displayed.</p>';

                showMessage(
                    "Unable to display the puzzle. Please try again.",
                    "error"
                );

                retryBtn.style.display = "inline-block";

                loading = false;
            };

            image.src = data.image;

        } catch (error) {

            question.innerHTML =
                '<p class="loading-text">Puzzle could not be loaded.</p>';

            showMessage(
                error.message + " You can try loading another puzzle.",
                "error"
            );

            retryBtn.style.display = "inline-block";

            loading = false;
        }
    }

    // Retry puzzle
    retryBtn.addEventListener("click", function() {
        loadPuzzle();
    });

    // Submit answer
    challengeForm.addEventListener("submit", async function(event) {

        event.preventDefault();

        if (gameFinished || submitting || loading) {
            return;
        }

        const answer = answerInput.value.trim();

        if (answer === "") {
            showMessage("Please enter your answer.", "error");
            return;
        }

        submitting = true;
        gameFinished = true;

        clearInterval(countdown);

        submitBtn.disabled = true;
        answerInput.disabled = true;

        timerStatus.textContent = "Checking answer...";

        try {

            const response = await fetch("../api/answer.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
                    answer: answer
                })

            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.error || "Could not submit your answer."
                );
            }

            if (data.correct) {

                // Update streak information
                streakCount.textContent = data.streak;
                streakBonus.textContent =
                    data.streak_bonus + " coins";

                let resultMessage =
                    "🏆 " + data.message +
                    " You earned " + data.coins +
                    " coins and " + data.score +
                    " points!";

                if (data.streak_bonus > 0) {
                    resultMessage +=
                        " 🔥 Streak bonus: " +
                        data.streak_bonus + " coins!";
                }

                if (data.event) {
                    resultMessage +=
                        " 🎲 Random Event: " + data.event;
                }

                showMessage(resultMessage, "success");

                timerStatus.textContent = "Completed";

                retryBtn.style.display = "none";
                links.style.display = "block";

            } else {

                // Update reset streak
                if (data.streak !== undefined) {
                    streakCount.textContent = data.streak;
                    streakBonus.textContent = "0 coins";
                }

                showMessage(
                    data.message || "Incorrect answer. Try again!",
                    "error"
                );

                timerStatus.textContent = "Challenge ended";

                retryBtn.style.display = "inline-block";
                retryBtn.textContent = "🔄 Try Another Puzzle";
            }

        } catch (error) {

            showMessage(
                error.message,
                "error"
            );

            timerStatus.textContent = "Submission failed";

            // Do not submit the same puzzle again if the server
            // may already have processed the request.
            retryBtn.style.display = "inline-block";

        } finally {
            submitting = false;
        }

    });

    // Load first puzzle
    loadPuzzle();

</script>

<?php endif; ?>

</body>
</html>
