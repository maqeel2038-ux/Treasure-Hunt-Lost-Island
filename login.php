<?php
session_start();
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare(
        "SELECT * FROM players WHERE username = ?"
    );
    $stmt->execute([$username]);

    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($player && password_verify($password, $player["password"])) {
        session_regenerate_id(true);

        $_SESSION["player_id"] = $player["player_id"];
        $_SESSION["username"] = $player["username"];

        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <h1>Welcome Back, Explorer!</h1>
    <p>Login to continue your adventure.</p>

    <?php if (isset($_GET["registered"])): ?>
        <p>Account created successfully! Please login.</p>
    <?php endif; ?>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="text" name="username"
               placeholder="Username" required>

        <br><br>

        <input type="password" name="password"
               placeholder="Password" required>

        <br><br>

        <button type="submit">Login</button>
    </form>

    <p>New explorer?
        <a href="register.php">Create an account</a>
    </p>

</body>
</html>