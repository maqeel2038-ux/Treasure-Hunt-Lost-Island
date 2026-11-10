<?php
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($username === "" || $email === "" || $password === "") {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        $check = $pdo->prepare(
            "SELECT player_id FROM players WHERE username = ? OR email = ?"
        );
        $check->execute([$username, $email]);

        if ($check->fetch()) {
            $message = "Username or email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO players (username, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([$username, $email, $hashedPassword]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <h1>Create Your Player Account</h1>
    <p>Join the adventure and discover the Lost Island!</p>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="text" name="username"
               placeholder="Username" required>

        <br><br>

        <input type="email" name="email"
               placeholder="Email address" required>

        <br><br>

        <input type="password" name="password"
               placeholder="Password (minimum 8 characters)"
               minlength="8" required>

        <br><br>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account?
        <a href="login.php">Login</a>
    </p>

</body>
</html>