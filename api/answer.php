<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once "../config/database.php";

if (empty($_SESSION['player_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in first.']);
    exit;
}

if (
    empty($_SESSION['puzzle_open']) ||
    !isset($_SESSION['puzzle_answer']) ||
    !isset($_SESSION['puzzle_location'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'No active puzzle found.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$userAnswer = trim((string)($input['answer'] ?? ''));

if ($userAnswer === '') {
    echo json_encode(['error' => 'Please enter an answer.']);
    exit;
}

$_SESSION['puzzle_open'] = false;

$correctAnswer = trim((string)$_SESSION['puzzle_answer']);
$location = $_SESSION['puzzle_location'];
$difficulty = $_SESSION['difficulty'] ?? 'Easy';

if ($userAnswer !== $correctAnswer) {
    unset($_SESSION['puzzle_answer']);

    echo json_encode([
        'correct' => false,
        'message' => 'Incorrect answer. Try again!'
    ]);
    exit;
}

// Base rewards for each island
$rewards = [
    'Beach' => ['coins' => 10, 'score' => 100],
    'HiddenJungle' => ['coins' => 20, 'score' => 150],
    'LostTemple' => ['coins' => 30, 'score' => 200]
];

// Difficulty multipliers
$multipliers = [
    'Easy' => 1,
    'Medium' => 2,
    'Hard' => 3
];

if (!isset($rewards[$location]) || !isset($multipliers[$difficulty])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid game settings.']);
    exit;
}

// Calculate rewards
$coins = $rewards[$location]['coins'] * $multipliers[$difficulty];
$score = $rewards[$location]['score'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO player_challenges (player_id, location)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $_SESSION['player_id'],
        $location
    ]);

    $stmt = $pdo->prepare("
        UPDATE players
        SET coins = coins + ?,
            score = score + ?
        WHERE player_id = ?
    ");

    $stmt->execute([
        $coins,
        $score,
        $_SESSION['player_id']
    ]);

    $pdo->commit();

    unset($_SESSION['puzzle_answer']);

    echo json_encode([
        'correct' => true,
        'message' => 'Correct answer! Treasure unlocked!',
        'coins' => $coins,
        'score' => $score
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    unset($_SESSION['puzzle_answer']);

    http_response_code(409);

    echo json_encode([
        'error' => 'This island may already be completed.'
    ]);
}
?>