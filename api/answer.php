
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once "../config/database.php";

// Send JSON error response
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);

    echo json_encode([
        'correct' => false,
        'error' => $message
    ]);

    exit;
}

// Check login
if (empty($_SESSION['player_id'])) {
    sendError('Please log in first.', 401);
}

// Check active puzzle
if (
    empty($_SESSION['puzzle_open']) ||
    !isset($_SESSION['puzzle_answer']) ||
    !isset($_SESSION['puzzle_location'])
) {
    sendError('No active puzzle found. Please load a new puzzle.');
}

// Read submitted answer
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input) || !isset($input['answer'])) {
    sendError('Invalid answer submission.');
}

$userAnswer = trim((string)$input['answer']);

if ($userAnswer === '') {
    sendError('Please enter an answer.');
}

if (strlen($userAnswer) > 100) {
    sendError('Your answer is too long.');
}

// Get puzzle information
$correctAnswer = trim((string)$_SESSION['puzzle_answer']);
$location = $_SESSION['puzzle_location'];
$difficulty = $_SESSION['difficulty'] ?? 'Easy';
$event = $_SESSION['puzzle_event'] ?? null;

$playerId = (int)$_SESSION['player_id'];

// Verify player account and current score
$stmt = $pdo->prepare("
    SELECT score
    FROM players
    WHERE player_id = ?
");

$stmt->execute([$playerId]);

$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    sendError('Player account not found.', 404);
}

$currentScore = (int)$player['score'];

// Verify island unlocking requirements
if ($location === 'HiddenJungle' && $currentScore < 100) {
    sendError('Hidden Jungle is locked.', 403);
}

if ($location === 'LostTemple' && $currentScore < 250) {
    sendError('Lost Temple is locked.', 403);
}

// Validate island rewards
$rewards = [
    'Beach' => [
        'coins' => 10,
        'score' => 100
    ],
    'HiddenJungle' => [
        'coins' => 20,
        'score' => 150
    ],
    'LostTemple' => [
        'coins' => 30,
        'score' => 200
    ]
];

// Difficulty multipliers
$multipliers = [
    'Easy' => 1,
    'Medium' => 2,
    'Hard' => 3
];

if (
    !isset($rewards[$location]) ||
    !isset($multipliers[$difficulty])
) {
    sendError('Invalid game settings.');
}

// Compare answers without case sensitivity
$isCorrect = (
    strtolower($userAnswer) === strtolower($correctAnswer)
);

// Incorrect answer
// Incorrect answer
if (!$isCorrect) {

    try {

        // Deduct 10 energy
        $energyPenalty = 10;

        $stmt = $pdo->prepare("
            UPDATE players
            SET energy = GREATEST(energy - ?, 0)
            WHERE player_id = ?
        ");

        $stmt->execute([
            $energyPenalty,
            $playerId
        ]);

        // Get remaining energy
        $stmt = $pdo->prepare("
            SELECT energy
            FROM players
            WHERE player_id = ?
        ");

        $stmt->execute([$playerId]);

        $remainingEnergy = (int)$stmt->fetchColumn();

        // Close current puzzle
        $_SESSION['puzzle_open'] = false;

        unset($_SESSION['puzzle_answer']);
        unset($_SESSION['puzzle_event']);

        // Reset winning streak
        $_SESSION['game_streak'] = 0;

        echo json_encode([
            'correct' => false,
            'message' => 'Incorrect answer! You lost 10 energy.',
            'streak' => 0,
            'energy_lost' => $energyPenalty,
            'energy' => $remainingEnergy
        ]);

        exit;

    } catch (Throwable $e) {

        sendError('Could not update your energy.', 500);
    }
}

// Calculate base rewards
$coins = $rewards[$location]['coins'] *
         $multipliers[$difficulty];

$score = $rewards[$location]['score'];

// Calculate consecutive-answer streak
$streak = (int)($_SESSION['game_streak'] ?? 0);
$streak++;

$_SESSION['game_streak'] = $streak;

// Combo bonus: 5 extra coins per consecutive correct answer,
// increasing up to a maximum of 25 bonus coins.
$streakBonus = min($streak * 5, 25);

$coins += $streakBonus;

// Apply random event
if (
    is_array($event) &&
    isset($event['type'], $event['amount'])
) {
    $eventAmount = max(0, (int)$event['amount']);

    if ($event['type'] === 'bonus_coins') {
        $coins += $eventAmount;
    }
}

try {

    $pdo->beginTransaction();

    // Record completed challenge
    $stmt = $pdo->prepare("
        INSERT INTO player_challenges
            (player_id, location)
        VALUES
            (?, ?)
    ");

    $stmt->execute([
        $playerId,
        $location
    ]);

    // Prepare player reward update
    $energyChange = 0;

    if (
        is_array($event) &&
        isset($event['type'], $event['amount'])
    ) {

        $eventAmount = max(0, (int)$event['amount']);

        if ($event['type'] === 'energy_loss') {
            $energyChange = -$eventAmount;
        }

        if ($event['type'] === 'energy_boost') {
            $energyChange = $eventAmount;
        }
    }

    // Update player statistics and energy
    if ($energyChange < 0) {

        $stmt = $pdo->prepare("
            UPDATE players
            SET
                coins = coins + ?,
                score = score + ?,
                energy = GREATEST(energy - ?, 0)
            WHERE player_id = ?
        ");

        $stmt->execute([
            $coins,
            $score,
            abs($energyChange),
            $playerId
        ]);

    } elseif ($energyChange > 0) {

        $stmt = $pdo->prepare("
            UPDATE players
            SET
                coins = coins + ?,
                score = score + ?,
                energy = LEAST(energy + ?, 100)
            WHERE player_id = ?
        ");

        $stmt->execute([
            $coins,
            $score,
            $energyChange,
            $playerId
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE players
            SET
                coins = coins + ?,
                score = score + ?
            WHERE player_id = ?
        ");

        $stmt->execute([
            $coins,
            $score,
            $playerId
        ]);
    }

    // Confirm that the player record was updated
    if ($stmt->rowCount() === 0) {

        $check = $pdo->prepare("
            SELECT player_id
            FROM players
            WHERE player_id = ?
        ");

        $check->execute([$playerId]);

        if (!$check->fetch()) {
            throw new Exception('Player account not found.');
        }
    }

    // Commit database changes
    // Check and award achievements before committing
// Check and award achievements before committing
$achievementMessages = [];

// Get completed puzzle count
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM player_challenges
    WHERE player_id = ?
");
$stmt->execute([$playerId]);
$totalPuzzles = (int)$stmt->fetchColumn();

// Get current player coins and score
$stmt = $pdo->prepare("
    SELECT coins, score
    FROM players
    WHERE player_id = ?
");
$stmt->execute([$playerId]);
$playerStats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalCoins = (int)$playerStats['coins'];
$totalScore = (int)$playerStats['score'];

// Get daily login streak
$stmt = $pdo->prepare("
    SELECT login_streak
    FROM daily_rewards
    WHERE player_id = ?
");
$stmt->execute([$playerId]);
$loginStreak = (int)$stmt->fetchColumn();

// Calculate unlocked islands
$totalIslands = 1;

if ($totalScore >= 100) {
    $totalIslands = 2;
}

if ($totalScore >= 250) {
    $totalIslands = 3;
}

// Get all achievements
$stmt = $pdo->query("
    SELECT achievement_id, achievement_name,
           badge_icon, requirement_type, requirement_value
    FROM achievements
");

$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check each achievement
foreach ($achievements as $achievement) {

    $currentValue = 0;

    switch ($achievement['requirement_type']) {

        case 'puzzles':
            $currentValue = $totalPuzzles;
            break;

        case 'coins':
            $currentValue = $totalCoins;
            break;

        case 'streak':
            $currentValue = $loginStreak;
            break;

        case 'islands':
            $currentValue = $totalIslands;
            break;
    }

    if ($currentValue >= (int)$achievement['requirement_value']) {

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO player_achievements
                (player_id, achievement_id)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $playerId,
            $achievement['achievement_id']
        ]);

        if ($stmt->rowCount() > 0) {

            $achievementMessages[] =
                $achievement['badge_icon'] . ' ' .
                $achievement['achievement_name'];
        }
    }
}

// Check game completion
$gameCompleted = false;

// Count completed islands
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT location)
    FROM player_challenges
    WHERE player_id = ?
      AND location IN ('Beach', 'HiddenJungle', 'LostTemple')
");

$stmt->execute([$playerId]);

$completedIslands = (int)$stmt->fetchColumn();

// Check if a victory record already exists
$stmt = $pdo->prepare("
    SELECT completion_id
    FROM game_completions
    WHERE player_id = ?
    LIMIT 1
");

$stmt->execute([$playerId]);

$alreadyWon = $stmt->fetch();

// Save victory only when all 3 islands are completed
if ($completedIslands >= 3 && !$alreadyWon) {

    // Get total puzzles solved
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM player_challenges
        WHERE player_id = ?
    ");

    $stmt->execute([$playerId]);

    $finalPuzzleCount = (int)$stmt->fetchColumn();

    // Get final player score
    $stmt = $pdo->prepare("
        SELECT score
        FROM players
        WHERE player_id = ?
    ");

    $stmt->execute([$playerId]);

    $finalScore = (int)$stmt->fetchColumn();

    // Save game completion
    $stmt = $pdo->prepare("
        INSERT INTO game_completions
            (player_id, final_score, coins_earned,
             puzzles_solved, islands_completed)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $playerId,
        $finalScore,
        $coins,
        $finalPuzzleCount,
        $completedIslands
    ]);

    $gameCompleted = true;
}

// Commit database changes
$pdo->commit();

    // Close puzzle and remove answer from session
    $_SESSION['puzzle_open'] = false;

    unset($_SESSION['puzzle_answer']);
    unset($_SESSION['puzzle_event']);

    // Prepare event description
    $eventDescription = null;
    $eventName = null;

    if (is_array($event)) {
        $eventDescription = $event['description'] ?? null;
        $eventName = $event['name'] ?? null;
    }

    // Return reward information
    echo json_encode([
    'correct' => true,
    'message' => 'Correct answer! Treasure unlocked!',
    'coins' => $coins,
    'base_coins' => $rewards[$location]['coins'] *
                    $multipliers[$difficulty],
    'streak_bonus' => $streakBonus,
    'streak' => $streak,
    'score' => $score,
    'difficulty' => $difficulty,
    'event' => $eventDescription,
    'event_name' => $eventName,
    'achievements' => $achievementMessages,
    'game_completed' => $gameCompleted
]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Prevent this puzzle from being submitted again
    $_SESSION['puzzle_open'] = false;

    unset($_SESSION['puzzle_answer']);
    unset($_SESSION['puzzle_event']);

    http_response_code(409);

    echo json_encode([
        'correct' => false,
        'error' => 'Unable to save your rewards. Please try again with a new puzzle.'
    ]);
}
?>
