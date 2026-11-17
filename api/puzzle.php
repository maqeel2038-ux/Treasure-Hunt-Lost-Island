
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Database connection
require_once "../config/database.php";

// Send JSON error
function sendError($message, $statusCode = 500) {
    http_response_code($statusCode);

    echo json_encode([
        'error' => $message
    ]);

    exit;
}

// Check login
if (empty($_SESSION['player_id'])) {
    sendError('Please log in first.', 401);
}

// Check player's energy
$playerId = $_SESSION['player_id'];

try {
    $stmt = $pdo->prepare("
        SELECT energy
        FROM players
        WHERE player_id = ?
    ");

    $stmt->execute([$playerId]);

    $energy = $stmt->fetchColumn();

    if ($energy === false) {
        sendError('Player account not found.', 404);
    }

    $energy = (int)$energy;

    if ($energy <= 0) {
        sendError(
            'You are out of energy! Recover your energy before continuing.',
            403
        );
    }

} catch (PDOException $e) {
    sendError('Could not check player energy.', 500);
}

// Validate location
$location = $_GET['location'] ?? 'Beach';

$allowedLocations = [
    'Beach',
    'HiddenJungle',
    'LostTemple'
];

if (!in_array($location, $allowedLocations, true)) {
    sendError('Invalid location.', 400);
}

// Banana API URL
$apiUrl = 'http://marcconrad.com/uob/banana/api.php?out=json';

// Connection settings
$context = stream_context_create([
    'http' => [
        'timeout' => 20,
        'ignore_errors' => true,
        'user_agent' => 'Mozilla/5.0 TreasureHuntGame/1.0'
    ]
]);

// Request puzzle
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false || trim($response) === '') {
    sendError(
        'Unable to connect to Banana API. Please try again.',
        502
    );
}

// Decode response
$data = json_decode($response, true);

if (!is_array($data)) {
    sendError('Banana API returned an invalid response.', 502);
}

// Validate required fields
if (
    empty($data['question']) ||
    !isset($data['solution'])
) {
    sendError('Banana API did not provide a complete puzzle.', 502);
}

// Validate image URL
$imageUrl = filter_var(
    $data['question'],
    FILTER_VALIDATE_URL
);

if (!$imageUrl) {
    sendError('Invalid puzzle image received.', 502);
}

$scheme = strtolower(parse_url($imageUrl, PHP_URL_SCHEME) ?? '');

if (!in_array($scheme, ['http', 'https'], true)) {
    sendError('Unsupported puzzle image URL.', 502);
}

// Random game events
$events = [
    [
        'name' => 'Tropical Storm',
        'description' => 'A tropical storm reduced your energy by 10!',
        'type' => 'energy_loss',
        'amount' => 10
    ],
    [
        'name' => 'Bonus Treasure',
        'description' => 'You discovered bonus treasure worth 10 coins!',
        'type' => 'bonus_coins',
        'amount' => 10
    ],
    [
        'name' => 'Energy Boost',
        'description' => 'You discovered an energy boost worth 10 energy!',
        'type' => 'energy_boost',
        'amount' => 10
    ]
];

$randomEvent = $events[random_int(0, count($events) - 1)];

// Store answer securely in session
$_SESSION['puzzle_location'] = $location;
$_SESSION['puzzle_event'] = $randomEvent;
$_SESSION['puzzle_answer'] = (string)$data['solution'];
$_SESSION['puzzle_open'] = true;

// Reset hint state
$_SESSION['hint_used'] = false;

// Return image only
echo json_encode([
    'image' => $imageUrl
]);

exit;
?>
