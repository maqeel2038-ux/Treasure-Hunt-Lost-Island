
<?php
session_start();

require_once "config/database.php";
require_once "vendor/autoload.php";

// Allow only players who have passed password verification
if (empty($_SESSION["pending_setup_player_id"])) {
    header("Location: login.php");
    exit;
}

$playerId = $_SESSION["pending_setup_player_id"];

$stmt = $pdo->prepare(
    "SELECT username, two_factor_enabled
     FROM players
     WHERE player_id = ?"
);

$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Already configured? Continue to normal 2FA login
if ((int)$player["two_factor_enabled"] === 1) {
    unset(
        $_SESSION["pending_setup_player_id"],
        $_SESSION["pending_setup_username"],
        $_SESSION["pending_2fa_secret"]
    );

    $_SESSION["pending_2fa_player_id"] = $playerId;
    $_SESSION["pending_2fa_username"] = $player["username"];

    header("Location: two_factor_login.php");
    exit;
}

// Generate a temporary secret
if (empty($_SESSION["pending_2fa_secret"])) {
    $google2fa = new \PragmaRX\Google2FA\Google2FA();
    $_SESSION["pending_2fa_secret"] = $google2fa->generateSecretKey();
}

$secret = $_SESSION["pending_2fa_secret"];

$google2fa = new \PragmaRX\Google2FA\Google2FA();

$qrCodeUrl = $google2fa->getQRCodeUrl(
    "Treasure Hunt",
    $player["username"],
    $secret
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up 2FA | Treasure Hunt</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

    <main class="login-container">
        <div class="login-card">

            <div class="login-icon">🔐</div>

            <h1>Set Up Two-Factor Authentication</h1>

            <p class="login-subtitle">
                Google Authenticator setup is required before entering the game.
            </p>

            <p>
                Open Google Authenticator and choose
                <strong>Enter a setup key</strong>.
            </p>

            <p>
                <strong>Account:</strong>
                <?php echo htmlspecialchars($player["username"]); ?>
            </p>

            <p><strong>Your setup key:</strong></p>

            <div style="background:#172740;padding:15px;border-radius:10px;
                        overflow-wrap:anywhere;color:#FFC629;">
                <?php echo htmlspecialchars($secret); ?>
            </div>

            <p style="color:#91a4bd;font-size:13px;">
                Keep this key private. Never share it.
            </p>

            <a href="<?php echo htmlspecialchars($qrCodeUrl); ?>"
               target="_blank"
               rel="noopener noreferrer">
                Open QR code
            </a>

            <p style="margin-top:20px;">
                After adding the account to Google Authenticator,
                continue to verification.
            </p>

            <a href="two_factor_verify.php"
               class="login-button"
               style="display:block;text-decoration:none;">
                Continue to Verification →
            </a>

        </div>
    </main>

</body>
</html>
