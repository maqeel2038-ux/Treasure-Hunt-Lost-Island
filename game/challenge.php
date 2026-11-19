
<?php
session_start();
require_once "../config/database.php";

// Check login
if (empty($_SESSION["player_id"])) {
    header("Location: ../login.php");
    exit();
}

// Get complete player information
$stmt = $pdo->prepare(
    "SELECT username, coins, score, energy
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

$score = (int)$player["score"];

// Get selected island
$location = $_GET["location"] ?? $_POST["location"] ?? "";

$challenges = [
    "Beach" => [
        "name" => "The Explorer's Beach",
        "difficulty" => "Beginner"
    ],
    "HiddenJungle" => [
        "name" => "The Hidden Jungle",
        "difficulty" => "Intermediate"
    ],
    "LostTemple" => [
        "name" => "The Lost Temple",
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
if ($location === "HiddenJungle" && $score < 100) {
    header("Location: island.php");
    exit();
}

if ($location === "LostTemple" && $score < 250) {
    header("Location: island.php");
    exit();
}

// Calculate next island progress
if ($location === "Beach") {
    $nextIslandScore = 100;
} elseif ($location === "HiddenJungle") {
    $nextIslandScore = 250;
} else {
    $nextIslandScore = 0;
}

$progressPercentage = $nextIslandScore > 0
    ? min(100, ($score / $nextIslandScore) * 100)
    : 100;

// Selected difficulty
$selectedDifficulty = $_SESSION["difficulty"] ?? "Easy";

$difficultyTimes = [
    "Easy" => 60,
    "Medium" => 45,
    "Hard" => 30
];

if (!isset($difficultyTimes[$selectedDifficulty])) {
    $selectedDifficulty = "Easy";
}

$gameTime = $difficultyTimes[$selectedDifficulty];

// Game progress
$alreadyCompleted = false;

$currentStreak = (int)($_SESSION["game_streak"] ?? 0);

// Save selected difficulty if submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $difficulty = $_POST["difficulty"] ?? "";

    if (isset($difficultyTimes[$difficulty])) {

        $_SESSION["difficulty"] = $difficulty;

        header(
            "Location: challenge.php?location=" .
            urlencode($location)
        );

        exit();
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet">

    
<style>
    * {
        box-sizing: border-box;
    }

    body {
        background:
            radial-gradient(circle at 10% 0%, rgba(45, 95, 150, 0.22), transparent 35%),
            radial-gradient(circle at 90% 90%, rgba(255, 198, 41, 0.06), transparent 30%),
            #0b1426;
        color: #f1f5f9;
        font-family: 'Outfit', sans-serif;
        padding: 20px;
        min-height: 100vh;
    }

    .challenge-container {
        max-width: 1000px;
        margin: 25px auto;
        background: rgba(19, 34, 56, 0.92);
        padding: 32px;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 24px;
        text-align: center;
        box-shadow: 0 25px 70px rgba(0,0,0,0.35);
    }

    .game-brand {
        color: #FFC629;
        letter-spacing: 3px;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 18px;
    }

    h1 {
        color: #ffffff;
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 900;
        margin-bottom: 12px;
    }

    .subtitle {
        color: #91a4bd;
        margin-bottom: 25px;
        font-size: 15px;
    }

    /* PLAYER STATS */

    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin: 25px 0;
    }

    .stat {
        background: #1c2e49;
        padding: 20px 12px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.07);
        color: #91a4bd;
        font-size: 13px;
        transition: 0.3s;
    }

    .stat:hover {
        border-color: rgba(255,198,41,0.3);
        transform: translateY(-3px);
    }

    .stat strong {
        display: block;
        color: #FFC629;
        font-size: 26px;
        font-weight: 800;
        margin-top: 9px;
    }

    /* ISLAND PROGRESS */

    .progress-section {
        background: #172740;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 15px;
        padding: 20px;
        margin: 25px 0;
    }

    .progress-section h3 {
        color: #FFC629;
        font-size: 16px;
        margin: 0 0 12px;
    }

    .progress-section p {
        color: #91a4bd;
        font-size: 13px;
    }

    .progress-bar {
        height: 9px;
        background: #30425f;
        border-radius: 20px;
        overflow: hidden;
        margin: 15px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #d99c13, #FFC629);
        border-radius: 20px;
        transition: width 0.5s;
    }

    /* GAME INFO */

    .game-info {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin: 22px 0;
    }

    .info-badge {
        background: #203454;
        border: 1px solid rgba(255,255,255,0.07);
        padding: 11px 17px;
        border-radius: 30px;
        font-size: 13px;
        color: #b8c6d9;
    }

    .info-badge strong {
        color: #FFC629;
    }

    /* MAIN GAME */

    .game-card {
        background: #14243d;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 20px;
        padding: 28px;
        margin-top: 25px;
    }

    .game-card h2 {
        font-size: 23px;
        color: #ffffff;
        margin: 0 0 10px;
    }

    .game-card > p {
        color: #91a4bd;
        font-size: 14px;
        line-height: 1.7;
    }

    /* STREAK */

    .streak-panel {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        background: linear-gradient(135deg, #302449, #203454);
        border: 1px solid rgba(255,198,41,0.25);
        border-radius: 16px;
        padding: 20px;
        margin: 25px 0;
    }

    .streak-panel div {
        color: #cbd5e1;
        font-size: 13px;
    }

    .streak-panel strong {
        display: block;
        color: #FFC629;
        font-size: 27px;
        font-weight: 800;
        margin-top: 8px;
    }

    /* TIMER */

    .timer-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
        font-size: 12px;
        color: #91a4bd;
        font-weight: 700;
        letter-spacing: 1px;
    }

    #timer {
        color: #FFC629;
        font-size: 52px;
        font-weight: 900;
        margin: 15px 0;
        font-variant-numeric: tabular-nums;
        transition: color 0.3s;
    }

    .timer-track {
        width: 100%;
        height: 10px;
        background: #30425f;
        border-radius: 20px;
        overflow: hidden;
    }

    .timer-progress {
        width: 100%;
        height: 100%;
        background: #FFC629;
        transition: width 1s linear, background 0.3s;
        border-radius: 20px;
    }

    /* PUZZLE */

    .question {
        min-height: 280px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 28px 0;
        background:
            radial-gradient(circle, rgba(55,90,130,0.25), transparent 70%),
            #0e1b30;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 18px;
        padding: 20px;
        overflow: hidden;
    }

    .question img {
        max-width: 100%;
        width: 460px;
        max-height: 400px;
        object-fit: contain;
        border-radius: 12px;
        background: white;
        box-shadow: 0 10px 35px rgba(0,0,0,0.25);
    }

    .loading-text {
        color: #FFC629;
        font-size: 15px;
        line-height: 1.7;
    }
/* GAME MODE SELECTOR */

.mode-selector {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin: 25px 0;
}

.mode-button {
    background: #203454;
    color: #cbd5e1;
    border: 1px solid #30425f;
    padding: 13px 22px;
    border-radius: 12px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.25s;
}

.mode-button.active {
    background: #FFC629;
    color: #142033;
    border-color: #FFC629;
}

.mode-button:hover {
    transform: translateY(-2px);
}

.trivia-question {
    color: #ffffff;
    font-size: 19px;
    font-weight: 700;
    line-height: 1.6;
}

.trivia-category {
    color: #FFC629;
    font-size: 12px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.trivia-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    width: 100%;
}

.trivia-option {
    background: #203454;
    color: #ffffff;
    border: 2px solid #30425f;
    padding: 16px;
    border-radius: 12px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.trivia-option:hover:not(:disabled) {
    border-color: #FFC629;
    background: #284366;
}

.trivia-option.selected {
    border-color: #FFC629;
    background: rgba(255,198,41,0.15);
}

.trivia-option:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

@media (max-width: 600px) {
    .trivia-options {
        grid-template-columns: 1fr;
    }
}


/* TREASURE DICTIONARY */

.dictionary-panel {
    background: #172740;
    border: 1px solid rgba(255,198,41,0.2);
    border-radius: 16px;
    padding: 22px;
    margin-top: 25px;
    text-align: left;
}

.dictionary-panel h3 {
    color: #FFC629;
    margin-top: 0;
    text-align: center;
}

.dictionary-input {
    width: 100%;
    padding: 14px;
    background: #0d192c;
    color: white;
    border: 2px solid #30425f;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    margin: 10px 0;
}

.dictionary-result {
    display: none;
    background: #203454;
    padding: 16px;
    border-radius: 12px;
    margin-top: 15px;
    line-height: 1.7;
    color: #cbd5e1;
}

.dictionary-result strong {
    color: #FFC629;
}

.dictionary-result p {
    margin-bottom: 0;
}

    /* ANSWER */

    .answer-input {
        width: 100%;
        padding: 17px;
        background: #0d192c;
        color: #ffffff;
        border: 2px solid #30425f;
        border-radius: 12px;
        font-size: 17px;
        margin: 15px 0;
        text-align: center;
        outline: none;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .answer-input::placeholder {
        color: #647790;
    }

    .answer-input:focus {
        border-color: #FFC629;
        box-shadow: 0 0 0 4px rgba(255,198,41,0.08);
    }

    /* BUTTONS */

    .game-button {
        display: inline-block;
        background: #FFC629;
        color: #142033;
        padding: 14px 25px;
        border: none;
        border-radius: 11px;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        margin: 6px;
        transition: 0.25s;
    }

    .game-button:hover {
        transform: translateY(-2px);
        background: #ffda69;
        box-shadow: 0 6px 20px rgba(255,198,41,0.12);
    }

    .game-button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .retry-button {
        background: #3b82b5;
        color: white;
    }

    .retry-button:hover {
        background: #559bd0;
    }

    /* HINT */

    .hint-section {
        margin: 20px 0;
    }

    .hint-button {
        background: #284f7d;
        color: #d9eaff;
    }

    .hint-button:hover {
        background: #3b6da0;
        color: white;
    }

    #hintMessage {
        color: #FFC629;
        font-size: 14px;
        margin-top: 12px;
        line-height: 1.7;
    }

    /* MESSAGE */

    .message {
        padding: 17px;
        border-radius: 12px;
        margin: 20px 0;
        line-height: 1.7;
        display: none;
        white-space: pre-line;
        font-size: 14px;
    }

    .success {
        display: block;
        background: rgba(22, 130, 75, 0.18);
        border: 1px solid #299c62;
        color: #85f0b5;
    }

    .error {
        display: block;
        background: rgba(160, 40, 40, 0.18);
        border: 1px solid #b94b4b;
        color: #ffaaaa;
    }

    /* LINKS */

    .links {
        margin-top: 25px;
    }

    .links a {
        display: inline-block;
        color: #FFC629;
        margin: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
    }

    .links a:hover {
        text-decoration: underline;
    }

    .game-instructions {
        color: #7288a4;
        font-size: 12px;
        margin-top: 22px;
        line-height: 1.7;
    }

    /* MOBILE */

    @media (max-width: 600px) {
        body {
            padding: 10px;
        }

        .challenge-container {
            padding: 20px 14px;
            margin: 10px auto;
            border-radius: 17px;
        }

        .stats {
            gap: 8px;
        }

        .stat {
            padding: 14px 5px;
            font-size: 11px;
        }

        .stat strong {
            font-size: 20px;
        }

        .game-info {
            gap: 7px;
        }

        .info-badge {
            padding: 9px 11px;
            font-size: 11px;
        }

        .game-card {
            padding: 18px 13px;
        }

        .game-card h2 {
            font-size: 20px;
        }

        .question {
            min-height: 200px;
            padding: 10px;
        }

        .question img {
            max-height: 300px;
        }

        #timer {
            font-size: 40px;
        }

        .streak-panel {
            padding: 15px 8px;
            gap: 8px;
        }

        .streak-panel strong {
            font-size: 22px;
        }

        .answer-input {
            font-size: 16px;
        }

        .game-button {
            width: 100%;
            margin: 7px 0;
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
        <strong id="coinsValue"><?php echo (int)$player["coins"]; ?></strong>
    </div>

    <div class="stat">
        ⭐ Score
        <strong id="scoreValue"><?php echo (int)$player["score"]; ?></strong>
    </div>

    <div class="stat">
        ⚡ Energy
        <strong id="energyValue"><?php echo (int)$player["energy"]; ?></strong>
    </div>

</div>

    <div class="progress-section">
    <h3>🏝️ Next Island Progress</h3>

    <p>
        <?php echo htmlspecialchars($player['score']); ?>
        / <?php echo $nextIslandScore; ?> points
    </p>

    <div class="progress-bar">
        <div class="progress-fill"
             style="width: <?php echo $progressPercentage; ?>%;">
        </div>
    </div>

    <?php if ($nextIslandScore > 0 && $progressPercentage >= 100): ?>
        <p>🎉 Next island unlocked!</p>
    <?php elseif ($nextIslandScore > 0): ?>
        <p>Keep solving puzzles to unlock the next island!</p>
    <?php else: ?>
        <p>🏆 You have unlocked all islands!</p>
    <?php endif; ?>
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


    <!-- GAME NAVIGATION -->
<div class="game-navigation">

    <a href="island.php" class="game-button">
        ← Back to Island
    </a>

    <a href="../dashboard.php" class="game-button">
        🏠 Dashboard
    </a>

    <button
        type="button"
        class="game-button retry-button"
        onclick="if(confirm('Are you sure you want to finish this game and return to the dashboard?')) { window.location.href='../dashboard.php'; }"
    >
        🏁 Finish Game
    </button>

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

    <div class="mode-selector">

        <button
            type="button"
            id="bananaModeBtn"
            class="mode-button active"
        >
            🍌 Banana Puzzle
        </button>

        <button
            type="button"
            id="triviaModeBtn"
            class="mode-button"
        >
            🧠 Trivia Challenge
        </button>

    </div>

    <h2 id="gameTitle">🧩 Solve the Banana Puzzle</h2>

            <p id="gameDescription">
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

            <!-- Hint system -->
            <div class="hint-section">

                <button
                    type="button"
                    id="hintBtn"
                    class="game-button hint-button"
                    disabled
                >
                    💡 Get a Hint
                </button>

                <p id="hintMessage"></p>

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

<button
    type="button"
    id="recoverEnergyBtn"
    class="game-button"
    style="display:none; margin-top:12px;"
>
    ⚡ Recover Energy (30 Coins)
</button>

            <div id="message" class="message"></div>

            <p class="game-instructions">
                💡 Tip: Check the picture carefully before submitting.
                Your time starts when the puzzle image has loaded.
            </p>

        </div>


<div class="dictionary-panel">

    <h3>📖 Treasure Dictionary</h3>

    <p style="color:#91a4bd; text-align:center; font-size:13px;">
        Discover the meaning of unfamiliar words during your adventure.
    </p>

    <input
        type="text"
        id="dictionaryWord"
        class="dictionary-input"
        placeholder="Enter a word (e.g. treasure)"
        maxlength="50"
    >

    <button
        type="button"
        id="dictionarySearchBtn"
        class="game-button"
        style="width:100%;"
    >
        🔍 Search Meaning
    </button>

    <div id="dictionaryResult" class="dictionary-result"></div>

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
    // Game mode elements
const bananaModeBtn = document.getElementById("bananaModeBtn");
const triviaModeBtn = document.getElementById("triviaModeBtn");
const gameTitle = document.getElementById("gameTitle");
const gameDescription = document.getElementById("gameDescription");

// Current game mode
let currentMode = "banana";
let selectedTriviaAnswer = "";

    const question = document.getElementById("question");
    const coinsValue = document.getElementById("coinsValue");
const scoreValue = document.getElementById("scoreValue");
const energyValue = document.getElementById("energyValue");
    const answerInput = document.getElementById("answer");
    const submitBtn = document.getElementById("submitBtn");
    const challengeForm = document.getElementById("challengeForm");
    const message = document.getElementById("message");
    const timer = document.getElementById("timer");
    const timerProgress = document.getElementById("timerProgress");
    const timerStatus = document.getElementById("timerStatus");
    const retryBtn = document.getElementById("retryBtn");
    const recoverEnergyBtn = document.getElementById("recoverEnergyBtn");
    const links = document.getElementById("links");

    const streakCount = document.getElementById("streakCount");
    const streakBonus = document.getElementById("streakBonus");

    // Hint elements
    const hintBtn = document.getElementById("hintBtn");
    const hintMessage = document.getElementById("hintMessage");

// Treasure Dictionary elements
const dictionaryWord = document.getElementById("dictionaryWord");
const dictionarySearchBtn = document.getElementById("dictionarySearchBtn");
const dictionaryResult = document.getElementById("dictionaryResult");

// Search Dictionary API
dictionarySearchBtn.addEventListener("click", async function() {

    const word = dictionaryWord.value.trim();

    if (!word) {
        dictionaryResult.style.display = "block";
        dictionaryResult.textContent = "Please enter a word.";
        return;
    }

    dictionarySearchBtn.disabled = true;
    dictionarySearchBtn.textContent = "Searching...";

    dictionaryResult.style.display = "block";
    dictionaryResult.textContent = "Looking up the word...";

    try {

        const response = await fetch(
            "../api/dictionary.php?word=" + encodeURIComponent(word),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Word not found.");
        }

        dictionaryResult.replaceChildren();

        const heading = document.createElement("strong");
        heading.textContent = data.word + " (" + data.part_of_speech + ")";

        const definition = document.createElement("p");
        definition.textContent = data.definition;

        dictionaryResult.append(heading, definition);

    } catch (error) {

        dictionaryResult.textContent = error.message;

    } finally {

        dictionarySearchBtn.disabled = false;
        dictionarySearchBtn.textContent = "🔍 Search Meaning";

    }

});


    let timeLeft = gameTime;
    let countdown = null;
    let gameFinished = false;
    let loading = false;
    let submitting = false;
    let hintUsed = false;
    // Switch between Banana Puzzle and Trivia modes
function switchGameMode(mode) {

    if (loading || submitting) {
        return;
    }

    currentMode = mode;

    bananaModeBtn.classList.toggle("active", mode === "banana");
    triviaModeBtn.classList.toggle("active", mode === "trivia");

    if (mode === "banana") {

        gameTitle.textContent = "🧩 Solve the Banana Puzzle";

        gameDescription.textContent =
            "Look carefully at the picture and enter the correct answer.";

    } else {

        gameTitle.textContent = "🧠 Trivia Challenge";

        gameDescription.textContent =
            "Answer the multiple-choice question before time runs out.";

    }

    loadCurrentChallenge();
}

// Mode button events
bananaModeBtn.addEventListener("click", function() {
    switchGameMode("banana");
});

triviaModeBtn.addEventListener("click", function() {
    console.log("Trivia button clicked!");
    switchGameMode("trivia");
});

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
                hintBtn.disabled = true;

                timerStatus.textContent = "Time is up!";

                showMessage(
                    "⏰ Time is up! You can try another puzzle.",
                    "error"
                );

                retryBtn.style.display = "inline-block";
            }

        }, 1000);
    }

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
answerInput.style.display = "block";
answerInput.disabled = true;
submitBtn.disabled = true;

    // Reset hint for the new puzzle
    hintUsed = false;
    hintBtn.disabled = true;
    hintBtn.textContent = "💡 Get a Hint";
    hintMessage.textContent = "";

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

        // Check for API errors
        if (!response.ok) {

            // Player has no energy
            if (response.status === 403) {

            recoverEnergyBtn.style.display = "inline-block";
retryBtn.style.display = "none";

                gameFinished = true;

                showMessage(
                    data.error ||
                    "You're out of energy! Recover your energy before continuing.",
                    "error"
                );

                timerStatus.textContent = "Out of energy";

                submitBtn.disabled = true;
                answerInput.disabled = true;
                hintBtn.disabled = true;

                retryBtn.style.display = "none";

                question.innerHTML =
                    '<p class="loading-text">⚡ You have no energy left!</p>';

                loading = false;
                return;
            }

            throw new Error(
                data.error || "Could not load puzzle."
            );
        }

        // Check puzzle image
        if (!data.image) {
            throw new Error("Puzzle image was not received.");
        }

        question.innerHTML =
            '<p class="loading-text">🧩 Loading puzzle image...</p>';

        const image = new Image();

        image.alt = "Banana picture puzzle";

        image.onload = function() {

            if (gameFinished) {
                loading = false;
                return;
            }

            question.replaceChildren(image);

            answerInput.disabled = false;
            submitBtn.disabled = false;

            // Enable hint after puzzle loads
            hintBtn.disabled = false;
            hintUsed = false;
            hintMessage.textContent = "";

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

// Load a Trivia question from Open Trivia DB
async function loadTrivia() {

    if (loading) return;

    loading = true;
    gameFinished = false;
    submitting = false;
    selectedTriviaAnswer = "";

    clearInterval(countdown);
    resetTimer();
    hideMessage();

    links.style.display = "none";
    retryBtn.style.display = "none";
    recoverEnergyBtn.style.display = "none";

    answerInput.value = "";
    answerInput.style.display = "none";
    answerInput.disabled = true;
    submitBtn.disabled = true;

    hintBtn.disabled = true;
    hintMessage.textContent = "";

    question.innerHTML =
        '<p class="loading-text">🧠 Loading trivia question...</p>';

    try {

        const response = await fetch(
            "../api/trivia.php?location=" +
            encodeURIComponent(locationName),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok) {

            if (response.status === 403) {

                gameFinished = true;

                showMessage(
                    data.error || "You are out of energy!",
                    "error"
                );

                question.innerHTML =
                    '<p class="loading-text">⚡ You have no energy left!</p>';

                recoverEnergyBtn.style.display = "inline-block";
                timerStatus.textContent = "Out of energy";

                loading = false;
                return;
            }

            throw new Error(data.error || "Could not load trivia.");
        }

        if (!data.question || !Array.isArray(data.answers) ||
            data.answers.length !== 4) {
            throw new Error("Invalid trivia question received.");
        }

        const category = document.createElement("div");
        category.className = "trivia-category";
        category.textContent =
            data.category + " • " + data.difficulty;

        const questionText = document.createElement("div");
        questionText.className = "trivia-question";
        questionText.textContent = data.question;

        const optionsContainer = document.createElement("div");
        optionsContainer.className = "trivia-options";

        question.replaceChildren(
            category,
            questionText,
            optionsContainer
        );

        data.answers.forEach(function(answer) {

            const option = document.createElement("button");

            option.type = "button";
            option.className = "trivia-option";
            option.textContent = answer;

            option.addEventListener("click", function() {

                if (gameFinished || submitting) return;

                selectedTriviaAnswer = answer;
                answerInput.value = answer;

                document.querySelectorAll(".trivia-option")
                    .forEach(function(button) {
                        button.classList.remove("selected");
                    });

                option.classList.add("selected");

                submitBtn.disabled = false;
            });

            optionsContainer.appendChild(option);

        });

        startTimer();

        submitBtn.disabled = true;

        loading = false;

    } catch (error) {

        question.innerHTML =
            '<p class="loading-text">Trivia question could not be loaded.</p>';

        showMessage(error.message, "error");

        retryBtn.style.display = "inline-block";

        loading = false;
    }
}

// Load the selected game mode
function loadCurrentChallenge() {

    if (currentMode === "trivia") {
        loadTrivia();
    } else {
        loadPuzzle();
    }

}

    // Hint system
    hintBtn.addEventListener("click", function() {

        if (hintUsed || gameFinished || loading) {
            return;
        }

        hintMessage.textContent =
            "💡 Hint: Look carefully at the picture. Count the objects and check for repeated patterns.";

        hintUsed = true;

        hintBtn.disabled = true;
        hintBtn.textContent = "Hint Used";
    });

    // Retry the current challenge mode
retryBtn.addEventListener("click", function() {
    loadCurrentChallenge();
});

    // Recover player energy
recoverEnergyBtn.addEventListener("click", async function() {

    recoverEnergyBtn.disabled = true;
    recoverEnergyBtn.textContent = "Recovering...";

    try {

        const response = await fetch("../api/recover_energy.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            }
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Energy recovery failed.");
        }

        // Update player statistics
        energyValue.textContent = data.energy;
        coinsValue.textContent = data.coins;

        showMessage(
            "⚡ " + data.message + " You recovered " +
            data.recovered + " energy!",
            "success"
        );

        recoverEnergyBtn.style.display = "none";
        recoverEnergyBtn.textContent = "⚡ Recover Energy (30 Coins)";

        // Load another puzzle
        loadPuzzle();

    } catch (error) {

        showMessage(error.message, "error");

        recoverEnergyBtn.disabled = false;
        recoverEnergyBtn.textContent = "⚡ Recover Energy (30 Coins)";
    }
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
        hintBtn.disabled = true;

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

            // Update coins and score immediately
coinsValue.textContent =
    Number(coinsValue.textContent) + Number(data.coins);

scoreValue.textContent =
    Number(scoreValue.textContent) + Number(data.score);

                // Update streak information
                streakCount.textContent = data.streak;
                streakBonus.textContent = data.streak_bonus + " coins";

                let resultMessage =
                    "🏆 " + (data.message || "Correct answer!") +
                    " You earned " + data.coins + " coins and " +
                    data.score + " points!";

                if (data.streak_bonus > 0) {
                    resultMessage +=
                        " 🔥 Streak bonus: " +
                        data.streak_bonus + " coins!";
                }

                if (data.event) {
                    resultMessage += " 🎲 Random Event: " + data.event;
                }
                // Display newly earned achievements
if (data.achievements && data.achievements.length > 0) {

    resultMessage += "\n🏅 NEW ACHIEVEMENT UNLOCKED!";

    data.achievements.forEach(function(achievement) {
        resultMessage += "\n" + achievement;
    });
}

                showMessage(resultMessage, "success");

                if (data.game_completed) {
    timerStatus.textContent = "🏆 VICTORY!";

    showMessage(
        "🏆 Congratulations! You conquered all three islands!",
        "success"
    );

    setTimeout(function() {
        window.location.href = "../victory.php";
    }, 3000);

    return;
}

                timerStatus.textContent = "Completed";
                retryBtn.style.display = "none";
                links.style.display = "none";

                // Automatically load the next challenge in the current mode
setTimeout(function() {
    loadCurrentChallenge();
}, 3000);
            } else {

    // Update energy after incorrect answer
    if (data.energy !== undefined) {
        energyValue.textContent = data.energy;
    }

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

            retryBtn.style.display = "inline-block";

        } finally {
            submitting = false;
        }

    });

    // Load the first challenge using the selected mode
loadCurrentChallenge();

</script>

<?php endif; ?>

</body>
</html>
