
<?php
session_start();
require_once "config/database.php";

$message = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $message = "Please enter your username and password.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT player_id, username, password
             FROM players
             WHERE username = ? OR email = ?"
        );

        $stmt->execute([$username, $username]);

        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($player && password_verify($password, $player["password"])) {

            session_regenerate_id(true);

            $_SESSION["player_id"] = $player["player_id"];
            $_SESSION["username"] = $player["username"];

            header("Location: dashboard.php");
            exit;

        } else {

            $message = "Invalid username/email or password.";

        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

    <button type="button" class="theme-toggle" id="themeToggle">
        ☀️ Light Mode
    </button>

    <main class="login-container">

        <div class="login-card">

            <div class="login-icon">🏝️</div>

            <h1>Welcome Back, Explorer!</h1>

            <p class="login-subtitle">
                Your adventure is waiting. Continue your journey!
            </p>

            <?php if (isset($_GET["registered"])): ?>
                <div class="success-message">
                    Account created successfully! Please login.
                </div>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <label for="username">Username or Email</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username or email"
                    value="<?php echo htmlspecialchars($username); ?>"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                    >
                        Show
                    </button>

                </div>

                <button type="submit" class="login-button">
                    Explore the Island →
                </button>

            </form>

            <div class="login-footer">
                <p>New explorer?</p>
                <a href="register.php">Create your account</a>
            </div>

            <div class="login-bottom">
                TREASURE HUNT: THE LOST ISLAND
            </div>

        </div>

    </main>

    <script>
        const themeToggle = document.getElementById("themeToggle");
        const passwordToggle = document.getElementById("passwordToggle");
        const passwordInput = document.getElementById("password");

        function applyTheme(theme) {
            document.body.classList.toggle("light-mode", theme === "light");
            themeToggle.textContent =
                theme === "light" ? "🌙 Dark Mode" : "☀️ Light Mode";
        }

        const savedTheme = localStorage.getItem("treasureTheme") || "dark";
        applyTheme(savedTheme);

        themeToggle.addEventListener("click", function () {
            const newTheme = document.body.classList.contains("light-mode")
                ? "dark"
                : "light";

            localStorage.setItem("treasureTheme", newTheme);
            applyTheme(newTheme);
        });

        passwordToggle.addEventListener("click", function () {
            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";
            passwordToggle.textContent = isHidden ? "Hide" : "Show";
        });
    </script>

</body>
</html>