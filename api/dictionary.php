<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

// Check login
if (empty($_SESSION["player_id"])) {
    http_response_code(401);
    echo json_encode([
        "error" => "Please log in first."
    ]);
    exit;
}

function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(["error" => $message]);
    exit;
}

$word = trim($_GET["word"] ?? "");

if ($word === "" || !preg_match("/^[a-zA-Z-]{1,50}$/", $word)) {
    sendError("Please provide a valid English word.");
}

$url = "https://api.dictionaryapi.dev/api/v2/entries/en/"
     . rawurlencode($word);

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
    sendError("Unable to connect to the Dictionary API.", 502);
}

$data = json_decode($response, true);

if (!isset($data[0]["meanings"][0]["definitions"][0]["definition"])) {
    sendError("No definition found for this word.", 404);
}

echo json_encode([
    "word" => $data[0]["word"],
    "part_of_speech" => $data[0]["meanings"][0]["partOfSpeech"],
    "definition" => $data[0]["meanings"][0]["definitions"][0]["definition"]
]);
