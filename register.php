
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



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <main class="auth-container">
        <div class="auth-card">

            <h1>Create Your Account</h1>
            <p>Join the adventure and discover the Lost Island!</p>

            <?php if ($message !== ""): ?>
                <p class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="register.php">

                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Choose a username"
                    value="<?php echo htmlspecialchars($username); ?>"
                    minlength="3"
                    maxlength="20"
                    required
                >

                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="At least 8 characters"
                    minlength="8"
                    required
                >

                <button type="submit">Create Account</button>

            </form>

            <p class="auth-link">
                Already have an account?
                <a href="login.php">Login here</a>
            </p>

        </div>
    </main>

</body>
</html>
