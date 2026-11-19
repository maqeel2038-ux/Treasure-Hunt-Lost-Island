<?php
session_start();

header("Content-Type: application/json");

require_once "../config/database.php";

function sendError($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode(["error" => $message]);
    exit;
}

// Check login
if (!isset($_SESSION["player_id"])) {
    sendError("Please log in to continue.", 401);
}

// Check player energy
$stmt = $pdo->prepare(
    "SELECT energy, score FROM players WHERE player_id = ?"
);

$stmt->execute([$_SESSION["player_id"]]);

$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    sendError("Player not found.", 404);
}

if ((int)$player["energy"] <= 0) {
    sendError("You do not have enough energy.", 403);
}

$score = (int)$player["score"];
$stmt->execute([$_SESSION["player_id"]]);

$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    sendError("Player not found.", 404);
}

if ((int)$player["energy"] <= 0) {
    sendError("You do not have enough energy.", 403);
}

// Validate island
$location = $_GET["location"] ?? "Beach";

$allowedLocations = ["Beach", "HiddenJungle", "LostTemple"];

if (!in_array($location, $allowedLocations, true)) {
    sendError("Invalid island location.", 400);
}

// Check island unlocking requirements
if ($location === "HiddenJungle" && $score < 100) {
    sendError("Hidden Jungle is locked. Reach 100 points first.", 403);
}

if ($location === "LostTemple" && $score < 250) {
    sendError("Lost Temple is locked. Reach 250 points first.", 403);
}

// Fetch a multiple-choice trivia question
$url = "https://opentdb.com/api.php?amount=1&type=multiple";

$options = [
    "http" => [
        "method" => "GET",
        "timeout" => 15,
        "ignore_errors" => true,
        "header" => "User-Agent: TreasureHuntGame/1.0\r\n"
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    sendError("Unable to connect to the Trivia API. Please try again.");
}

$data = json_decode($response, true);

if (
    !$data ||
    !isset($data["response_code"]) ||
    $data["response_code"] !== 0 ||
    empty($data["results"][0])
) {
    sendError("No trivia question is available right now. Please try again.");
}

$trivia = $data["results"][0];

// Decode HTML entities
$question = html_entity_decode(
    $trivia["question"],
    ENT_QUOTES | ENT_HTML5,
    "UTF-8"
);

$correctAnswer = html_entity_decode(
    $trivia["correct_answer"],
    ENT_QUOTES | ENT_HTML5,
    "UTF-8"
);

$incorrectAnswers = array_map(
    function ($answer) {
        return html_entity_decode(
            $answer,
            ENT_QUOTES | ENT_HTML5,
            "UTF-8"
        );
    },
    $trivia["incorrect_answers"]
);

// Combine and shuffle options
$answers = $incorrectAnswers;
$answers[] = $correctAnswer;

shuffle($answers);

// Store the answer using the existing game session
$_SESSION["puzzle_location"] = $location;
$_SESSION["puzzle_event"] = null;
$_SESSION["puzzle_answer"] = $correctAnswer;
$_SESSION["puzzle_open"] = true;
$_SESSION["hint_used"] = false;

// Return question and choices
echo json_encode([
    "question" => $question,
    "answers" => $answers,
    "category" => html_entity_decode(
        $trivia["category"],
        ENT_QUOTES | ENT_HTML5,
        "UTF-8"
    ),
    "difficulty" => $trivia["difficulty"]
]);