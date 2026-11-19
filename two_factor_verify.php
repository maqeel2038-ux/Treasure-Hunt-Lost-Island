
<?php
session_start();

require_once "config/database.php";
require_once "vendor/autoload.php";

// Only allow players who have passed password verification
if (empty($_SESSION["pending_setup_player_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["pending_2fa_secret"])) {
    header("Location: two_factor_setup.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST["code"] ?? "");
    $secret = $_SESSION["pending_2fa_secret"];
    $playerId = $_SESSION["pending_setup_player_id"];

    if (!preg_match('/^\d{6}$/', $code)) {

        $message = "Please enter a valid 6-digit code.";

    } else {

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        if ($google2fa->verifyKey($secret, $code, 1)) {

            $stmt = $pdo->prepare(
                "UPDATE players
                 SET two_factor_secret = ?,
                     two_factor_enabled = 1
                 WHERE player_id = ?"
            );

            $stmt->execute([$secret, $playerId]);

            // Promote the player to an authenticated session
            session_regenerate_id(true);

            $_SESSION["player_id"] = $playerId;
            $_SESSION["username"] = $_SESSION["pending_setup_username"];

            // Remove temporary setup information
            unset(
                $_SESSION["pending_setup_player_id"],
                $_SESSION["pending_setup_username"],
                $_SESSION["pending_2fa_secret"]
            );

            header("Location: dashboard.php?2fa=enabled");
            exit;

        } else {

            $message = "Incorrect code. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify 2FA | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

    <main class="login-container">
        <div class="login-card">

            <div class="login-icon">🔐</div>

            <h1>Verify Your Authenticator</h1>

            <p class="login-subtitle">
                Enter the six-digit code displayed in Google Authenticator.
            </p>

            <?php if ($message !== ""): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="two_factor_verify.php">

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
                    Verify and Enable 2FA →
                </button>

            </form>

        </div>
    </main>

</body>
</html>
