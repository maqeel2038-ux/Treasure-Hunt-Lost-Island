
<?php
session_start();
require_once "../config/database.php";

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
        "coins" => 10,
        "score" => 100,
        "difficulty" => "Beginner"
    ],

    "HiddenJungle" => [
        "name" => "Hidden Jungle",
        "coins" => 20,
        "score" => 150,
        "difficulty" => "Intermediate"
    ],

    "LostTemple" => [
        "name" => "Lost Temple",
        "coins" => 30,
        "score" => 200,
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($challenge["name"]); ?> | Treasure Hunt</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #101d38;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .challenge-container {
            max-width: 650px;
            margin: 50px auto;
            background: #1e2d49;
            padding: 35px;
            border-radius: 15px;
            text-align: center;
        }

        h1 {
            color: #ffc629;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 15px;
            margin: 25px 0;
        }

        .stat {
            background: #263a5d;
            padding: 12px 18px;
            border-radius: 10px;
        }

        .question {
            margin: 30px 0;
        }

        .question img {
            max-width: 100%;
            width: 400px;
            border-radius: 10px;
            background: white;
        }

        #timer {
            font-size: 35px;
            font-weight: bold;
            color: #ffc629;
            margin: 20px;
        }

        input[type="text"] {
            width: 85%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        button {
            background: #ffc629;
            color: #101d38;
            padding: 13px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .success {
            background: #155d3b;
        }

        .error {
            background: #7b2525;
        }

        .links {
            margin-top: 25px;
        }

        .links a {
            color: #ffc629;
            margin: 10px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="challenge-container">

    <h1>🏝️ Treasure Hunt</h1>

    <h2><?php echo htmlspecialchars($challenge["name"]); ?></h2>

    <p>
        Welcome, <?php echo htmlspecialchars($player["username"]); ?>!
    </p>

    <div class="stats">

        <div class="stat">
            🪙 Coins: <?php echo (int)$player["coins"]; ?>
        </div>

        <div class="stat">
            ⭐ Score: <?php echo (int)$player["score"]; ?>
        </div>

        <div class="stat">
            ⚡ Energy: <?php echo (int)$player["energy"]; ?>
        </div>

    </div>

    <p>
        Game Mode:
        <strong>
            <?php echo htmlspecialchars($_SESSION["difficulty"] ?? "Easy"); ?>
        </strong>
    </p>

    <p>
        Time Limit:
        <strong>
            <?php
            $selectedDifficulty = $_SESSION["difficulty"] ?? "Easy";

            echo $selectedDifficulty === "Hard" ? 30 :
                ($selectedDifficulty === "Medium" ? 45 : 60);
            ?>
            seconds
        </strong>
    </p>

    <?php if ($alreadyCompleted): ?>

        <div class="message success">
            You have already completed this challenge!
            No additional rewards are available.
        </div>

        <div class="links">
            <a href="island.php">Back to Island</a>
            <a href="../dashboard.php">Dashboard</a>
        </div>

    <?php else: ?>

        <p>Solve the picture puzzle before time runs out!</p>

        <div id="timer">
            <?php
            echo $selectedDifficulty === "Hard" ? 30 :
                ($selectedDifficulty === "Medium" ? 45 : 60);
            ?>
        </div>

        <div class="question" id="question">
            Loading Banana puzzle...
        </div>

        <form id="challengeForm">

            <input
                type="text"
                id="answer"
                placeholder="Enter your answer..."
                autocomplete="off"
                required
                disabled
            >

            <br>

            <button type="submit" id="submitBtn" disabled>
                Submit Answer
            </button>

        </form>

        <div id="message"></div>

        <div class="links" id="links" style="display:none;">
            <a href="island.php">Back to Island</a>
            <a href="../dashboard.php">Dashboard</a>
        </div>

    <?php endif; ?>

</div>

<?php if (!$alreadyCompleted): ?>

<script>

    const locationName = <?php echo json_encode($location); ?>;

    const question = document.getElementById("question");
    const answerInput = document.getElementById("answer");
    const submitBtn = document.getElementById("submitBtn");
    const challengeForm = document.getElementById("challengeForm");
    const message = document.getElementById("message");
    const timer = document.getElementById("timer");
    const links = document.getElementById("links");

    const difficulty = <?php echo json_encode($_SESSION["difficulty"] ?? "Easy"); ?>;

    let timeLeft = 60;

    if (difficulty === "Medium") {
        timeLeft = 45;
    } else if (difficulty === "Hard") {
        timeLeft = 30;
    }

    let countdown;
    let gameFinished = false;

    // Display messages
    function showMessage(text, type) {
        message.className = "message " + type;
        message.textContent = text;
    }

    // Load puzzle from Banana API through PHP
    async function loadPuzzle() {

        try {

            const response = await fetch(
                "../api/puzzle.php?location=" +
                encodeURIComponent(locationName)
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || "Could not load puzzle.");
            }

            if (!data.image) {
                throw new Error("Puzzle image was not received.");
            }

            const image = document.createElement("img");

            image.src = data.image;
            image.alt = "Banana picture puzzle";

            image.onload = function() {
                answerInput.disabled = false;
                submitBtn.disabled = false;
                answerInput.focus();
                startTimer();
            };

            image.onerror = function() {
                question.textContent = "Puzzle image could not be displayed.";
                showMessage("Please refresh the page and try again.", "error");
            };

            question.innerHTML = "";
            question.appendChild(image);

        } catch (error) {

            question.textContent = error.message;

            showMessage(
                "Unable to load the puzzle. Please refresh the page.",
                "error"
            );
        }
    }

    // Start countdown
    function startTimer() {

        countdown = setInterval(function() {

            timeLeft--;

            timer.textContent = timeLeft;

            if (timeLeft <= 10) {
                timer.style.color = "red";
            }

            if (timeLeft <= 0) {

                clearInterval(countdown);

                gameFinished = true;

                answerInput.disabled = true;
                submitBtn.disabled = true;

                showMessage(
                    "Time is up! Challenge failed. Please return to the island.",
                    "error"
                );

                links.style.display = "block";
            }

        }, 1000);
    }

    // Submit answer to PHP
    challengeForm.addEventListener("submit", async function(event) {

        event.preventDefault();

        if (gameFinished) {
            return;
        }

        const answer = answerInput.value.trim();

        if (answer === "") {
            showMessage("Please enter your answer.", "error");
            return;
        }

        submitBtn.disabled = true;
        answerInput.disabled = true;

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

            clearInterval(countdown);

            gameFinished = true;

            if (data.correct) {

                let resultMessage =
                    data.message +
                    " You earned " + data.coins +
                    " coins and " + data.score + " points!";

                if (data.event) {
                    resultMessage += " 🎲 Random Event: " + data.event;
                }

                showMessage(resultMessage, "success");

            } else {

                showMessage(
                    data.message || data.error || "Incorrect answer.",
                    "error"
                );

            }

            links.style.display = "block";

        } catch (error) {

            showMessage(
                "Something went wrong. Please try again.",
                "error"
            );

            answerInput.disabled = false;
            submitBtn.disabled = false;
        }
    });

    // Load the first puzzle
    loadPuzzle();

</script>

<?php endif; ?>

</body>
</html>
