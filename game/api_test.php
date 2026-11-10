
<?php

$apiUrl = "http://marcconrad.com/uob/banana/api.php?out=json";

$context = stream_context_create([
    "http" => [
        "timeout" => 15
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    die("API connection failed. Check your internet connection and PHP allow_url_fopen setting.");
}

$data = json_decode($response, true);

if (!is_array($data)) {
    die("The API did not return valid JSON.");
}

if (!isset($data["question"]) || !isset($data["solution"])) {
    echo "<h2>API Response:</h2>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    exit();
}

echo "<h1>Banana API Connected Successfully!</h1>";

echo "<h3>Question Image:</h3>";

echo '<img src="' .
    htmlspecialchars($data["question"], ENT_QUOTES, "UTF-8") .
    '" style="max-width:400px;">';

echo "<h3>Solution:</h3>";

echo htmlspecialchars((string)$data["solution"]);

?>
