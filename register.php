
<?php
require_once "config/database.php";

$message = "";

$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $email === "" || $password === "") {
        $message = "Please fill in all fields.";

    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $message = "Username must be between 3 and 20 characters.";

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
