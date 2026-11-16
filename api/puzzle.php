
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Helper function for API errors
function sendError($message, $statusCode) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
}

// Check whether the player is logged in
if (empty($_SESSION['player_id'])) {
    sendError('Please log in first.', 401);
}

// Get and validate island location
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
$apiUrl = "http://marcconrad.com/uob/banana/api.php?out=json";

// Set connection options
$context = stream_context_create([
    "http" => [
        "timeout" => 15,
        "ignore_errors" => true,
        "user_agent" => "TreasureHuntGame/1.0"
    ]
]);

// Connect to Banana API
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    sendError('Could not connect to Banana API. Please try again.', 502);
}

// Check HTTP response status
$statusLine = $http_response_header[0] ?? '';

if (!preg_match('/\s200\s/', $statusLine)) {
    sendError('Banana API is currently unavailable.', 502);
}

// Decode API response
$data = json_decode($response, true);

// Validate API response
if (
    !is_array($data) ||
    !isset($data['question']) ||
    !isset($data['solution']) ||
    !is_string($data['question']) ||
    !is_scalar($data['solution'])
) {
    sendError('Invalid puzzle response from Banana API.', 502);
}

// Validate puzzle image URL
$imageUrl = filter_var($data['question'], FILTER_VALIDATE_URL);

if (
    !$imageUrl ||
    !in_array(parse_url($imageUrl, PHP_URL_SCHEME), ['http', 'https'], true)
) {
    sendError('Invalid puzzle image URL.', 502);
}

// Generate random game event
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

// Save puzzle information securely in PHP session
$_SESSION['puzzle_location'] = $location;
$_SESSION['puzzle_event'] = $randomEvent;
$_SESSION['puzzle_answer'] = (string)$data['solution'];
$_SESSION['puzzle_open'] = true;

// Send only the image to the browser
echo json_encode([
    'image' => $imageUrl
]);
?>
