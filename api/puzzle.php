
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Check whether the player is logged in
if (empty($_SESSION['player_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in first.']);
    exit;
}

// Get the current island location
$location = $_GET['location'] ?? 'Beach';

$allowedLocations = ['Beach', 'HiddenJungle', 'LostTemple'];

if (!in_array($location, $allowedLocations, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid location.']);
    exit;
}

$_SESSION['puzzle_location'] = $location;

// Banana API URL
$apiUrl = "http://marcconrad.com/uob/banana/api.php?out=json";

// Connect to the API
$context = stream_context_create([
    "http" => [
        "timeout" => 15
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not connect to Banana API.']);
    exit;
}

// Convert API response into PHP data
$data = json_decode($response, true);

// Check API response
if (
    !is_array($data) ||
    !isset($data['question']) ||
    !isset($data['solution'])
) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid puzzle response.']);
    exit;
}

// Save the correct answer securely in the session
$_SESSION['puzzle_answer'] = (string)$data['solution'];
$_SESSION['puzzle_open'] = true;

// Send only the puzzle image to the browser
echo json_encode([
    'image' => $data['question']
]);
?>
