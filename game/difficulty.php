<?php
session_start();

if (!isset($_SESSION["player_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $difficulty = $_POST["difficulty"] ?? "";

    $allowed = ["Easy", "Medium", "Hard"];

    if (in_array($difficulty, $allowed, true)) {
        $_SESSION["difficulty"] = $difficulty;

        header("Location: island.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Difficulty - Treasure Hunt</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="container">
    <h1>Choose Your Difficulty</h1>
    <p>Select how challenging your treasure hunt should be!</p>

    <form method="POST">
        <button type="submit" name="difficulty" value="Easy">
            Easy - 60 Seconds
        </button>

        <button type="submit" name="difficulty" value="Medium">
            Medium - 45 Seconds
        </button>

        <button type="submit" name="difficulty" value="Hard">
            Hard - 30 Seconds
        </button>
    </form>

    <p><a href="../dashboard.php">Back to Dashboard</a></p>
</div>

</body>
</html>