
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
if (!$isCorrect) {

    // Close and clear this puzzle
    $_SESSION['puzzle_open'] = false;

    unset($_SESSION['puzzle_answer']);
    unset($_SESSION['puzzle_event']);

    // Reset consecutive correct-answer streak
    $_SESSION['game_streak'] = 0;

    echo json_encode([
        'correct' => false,
        'message' => 'Incorrect answer! Your streak has been reset.',
        'streak' => 0
    ]);

    exit;
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
        'event_name' => $eventName
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
