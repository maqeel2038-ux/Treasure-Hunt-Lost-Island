
<?php
session_start();

require_once "config/database.php";
require_once "vendor/autoload.php";

if (empty($_SESSION["pending_2fa_player_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST["code"] ?? "");

    if (!preg_match('/^\d{6}$/', $code)) {

        $message = "Please enter a valid 6-digit code.";

    } else {

        $stmt = $pdo->prepare(
            "SELECT two_factor_secret, two_factor_enabled
             FROM players
             WHERE player_id = ?"
        );

        $stmt->execute([$_SESSION["pending_2fa_player_id"]]);

        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        if (
            $player &&
            (int)$player["two_factor_enabled"] === 1 &&
            !empty($player["two_factor_secret"]) &&
            $google2fa->verifyKey(
                $player["two_factor_secret"],
                $code,
                1
            )
        ) {

            session_regenerate_id(true);

            $_SESSION["player_id"] =
                $_SESSION["pending_2fa_player_id"];

            $_SESSION["username"] =
                $_SESSION["pending_2fa_username"];

            unset(
                $_SESSION["pending_2fa_player_id"],
                $_SESSION["pending_2fa_username"]
            );

            header("Location: dashboard.php");
            exit;

        } else {

            $message = "Incorrect authentication code. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Login | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

    <main class="login-container">
        <div class="login-card">

            <div class="login-icon">🔐</div>

            <h1>Verify Your Identity</h1>

            <p class="login-subtitle">
                Enter the six-digit code from your Google Authenticator app.
            </p>

            <?php if ($message !== ""): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="two_factor_login.php">

                <label for="code">Authentication Code</label>

                <input
                    type="text"
                    id="code"
                    name="code"
                    placeholder="Enter 6-digit code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                >

                <button type="submit" class="login-button">
                    Verify and Login →
                </button>

            </form>

        </div>
    </main>

</body>
</html>
