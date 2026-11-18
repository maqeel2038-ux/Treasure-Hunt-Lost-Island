<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["player_id"])) {
    header("Location: login.php");
    exit;
}

$playerId = $_SESSION["player_id"];

$message = "";
$messageType = "";

// Update username
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_username"])) {

    $newUsername = trim($_POST["username"]);

    if (strlen($newUsername) < 3 || strlen($newUsername) > 30) {
        $message = "Username must be between 3 and 30 characters.";
        $messageType = "error";
    } else {

        $stmt = $pdo->prepare("
            SELECT player_id FROM players
            WHERE username = ? AND player_id != ?
        ");

        $stmt->execute([$newUsername, $playerId]);

        if ($stmt->fetch()) {
            $message = "This username is already taken.";
            $messageType = "error";
        } else {

            $stmt = $pdo->prepare("
                UPDATE players
                SET username = ?
                WHERE player_id = ?
            ");

            $stmt->execute([$newUsername, $playerId]);

            $_SESSION["username"] = $newUsername;

            $message = "Username updated successfully!";
            $messageType = "success";
        }
    }
}

// Change password
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    $currentPassword = $_POST["current_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    $stmt = $pdo->prepare("
        SELECT password FROM players
        WHERE player_id = ?
    ");

    $stmt->execute([$playerId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account || !password_verify($currentPassword, $account["password"])) {

        $message = "Current password is incorrect.";
        $messageType = "error";

    } elseif (strlen($newPassword) < 8) {

        $message = "New password must contain at least 8 characters.";
        $messageType = "error";

    } elseif ($newPassword !== $confirmPassword) {

        $message = "New passwords do not match.";
        $messageType = "error";

    } else {

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE players
            SET password = ?
            WHERE player_id = ?
        ");

        $stmt->execute([$hashedPassword, $playerId]);

        $message = "Password changed successfully!";
        $messageType = "success";
    }
}

// Get player information
$stmt = $pdo->prepare("
    SELECT
        player_id,
        username,
        email,
        coins,
        score,
        energy,
        current_location,
        created_at
    FROM players
    WHERE player_id = ?
");

$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Player Profile | Treasure Hunt</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #101D38;
            color: white;
        }

        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-back {
            display: inline-block;
            color: #AAB8D0;
            text-decoration: none;
            margin-bottom: 25px;
            transition: 0.2s;
        }

        .profile-back:hover {
            color: #FFC629;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 22px;
            padding: 30px;
            background: linear-gradient(135deg, #263A5D, #1A2B49);
            border: 1px solid #3A5073;
            border-radius: 22px;
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            flex-shrink: 0;
            border-radius: 50%;
            background: rgba(255,198,41,0.12);
            border: 2px solid #FFC629;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
        }

        .profile-header-text h1 {
            font-size: 30px;
            font-weight: 900;
            color: #FFC629;
            margin: 0 0 8px;
            overflow-wrap: anywhere;
        }

        .profile-header-text p {
            color: #AAB8D0;
            margin: 0;
        }

        .player-tag {
            display: inline-block;
            margin-top: 12px;
            background: rgba(255,198,41,0.12);
            border: 1px solid rgba(255,198,41,0.25);
            color: #FFC629;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-message {
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-weight: 600;
        }

        .profile-message.success {
            background: rgba(40,167,69,0.12);
            border: 1px solid rgba(40,167,69,0.3);
            color: #65E58B;
        }

        .profile-message.error {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.3);
            color: #FF7777;
        }

        .profile-card {
            background: #1A2B49;
            border: 1px solid #314565;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .profile-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .section-icon {
            width: 43px;
            height: 43px;
            background: rgba(255,198,41,0.1);
            border: 1px solid rgba(255,198,41,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .profile-card-header h2 {
            font-size: 21px;
            color: #FFC629;
            margin: 0;
            font-weight: 800;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            background: #101D38;
            border: 1px solid #2C405F;
            border-radius: 13px;
            padding: 18px;
            min-width: 0;
        }

        .info-item span {
            display: block;
            color: #91A3BF;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .info-item strong {
            display: block;
            color: white;
            font-size: 16px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: #101D38;
            border: 1px solid #2C405F;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255,198,41,0.4);
        }

        .stat-icon {
            font-size: 27px;
            margin-bottom: 12px;
        }

        .stat-label {
            color: #91A3BF;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #FFC629;
            font-size: 23px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .stat-card.location .stat-value {
            font-size: 15px;
            color: #65D6A0;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 13px;
            max-width: 550px;
        }

        .profile-form label {
            color: #C5D1E3;
            font-size: 14px;
            font-weight: 600;
        }

        .profile-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 14px 16px;
            border-radius: 11px;
            border: 1px solid #3A5073;
            background: #101D38;
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        .profile-form input:focus {
            border-color: #FFC629;
            box-shadow: 0 0 0 3px rgba(255,198,41,0.08);
        }

        .profile-form input::placeholder {
            color: #7185A4;
        }

        .profile-form button {
            padding: 14px 18px;
            margin-top: 8px;
            background: #FFC629;
            color: #101D38;
            border: none;
            border-radius: 11px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s;
        }

        .profile-form button:hover {
            background: #E6AD13;
            transform: translateY(-2px);
        }

        .form-description {
            color: #91A3BF;
            font-size: 13px;
            margin: -5px 0 5px;
        }

        .profile-footer {
            text-align: center;
            color: #7185A4;
            font-size: 13px;
            margin: 30px 0 10px;
        }

        @media (max-width: 800px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .profile-container {
                padding: 14px;
                margin: 20px auto;
            }

            .profile-header {
                padding: 22px;
                gap: 15px;
            }

            .profile-avatar {
                width: 65px;
                height: 65px;
                font-size: 30px;
            }

            .profile-header-text h1 {
                font-size: 23px;
            }

            .profile-card {
                padding: 18px;
                border-radius: 16px;
            }

            .profile-card-header h2 {
                font-size: 18px;
            }

            .profile-info {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-grid {
                gap: 10px;
            }

            .stat-card {
                padding: 15px 10px;
            }

            .stat-value {
                font-size: 20px;
            }

            .stat-icon {
                font-size: 23px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="profile-container">

    <a href="dashboard.php" class="profile-back">
        ← Back to Dashboard
    </a>

    <!-- Profile Header -->
    <header class="profile-header">

        <div class="profile-avatar">
            👤
        </div>

        <div class="profile-header-text">

            <h1>
                <?php echo htmlspecialchars($player["username"]); ?>
            </h1>

            <p>Manage your explorer account and personal information.</p>

            <span class="player-tag">
                🏝️ ISLAND EXPLORER
            </span>

        </div>

    </header>

    <!-- Messages -->
    <?php if ($message !== ""): ?>

        <div class="profile-message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <!-- Account Information -->
    <section class="profile-card">

        <div class="profile-card-header">

            <div class="section-icon">👤</div>

            <h2>Account Information</h2>

        </div>

        <div class="profile-info">

            <div class="info-item">
                <span>PLAYER ID</span>
                <strong>#<?php echo (int)$player["player_id"]; ?></strong>
            </div>

            <div class="info-item">
                <span>USERNAME</span>
                <strong><?php echo htmlspecialchars($player["username"]); ?></strong>
            </div>

            <div class="info-item">
                <span>EMAIL ADDRESS</span>
                <strong><?php echo htmlspecialchars($player["email"]); ?></strong>
            </div>

            <div class="info-item">
                <span>REGISTRATION DATE</span>
                <strong>
                    <?php echo date("d M Y", strtotime($player["created_at"])); ?>
                </strong>
            </div>

        </div>

    </section>

    <!-- Explorer Statistics -->
    <section class="profile-card">

        <div class="profile-card-header">

            <div class="section-icon">🏆</div>

            <h2>Explorer Statistics</h2>

        </div>

        <div class="stat-grid">

            <div class="stat-card">

                <div class="stat-icon">🪙</div>

                <div class="stat-label">GOLD COINS</div>

                <div class="stat-value">
                    <?php echo (int)$player["coins"]; ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-icon">🏆</div>

                <div class="stat-label">TOTAL SCORE</div>

                <div class="stat-value">
                    <?php echo (int)$player["score"]; ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-icon">⚡</div>

                <div class="stat-label">ENERGY</div>

                <div class="stat-value">
                    <?php echo (int)$player["energy"]; ?>%
                </div>

            </div>

            <div class="stat-card location">

                <div class="stat-icon">🏝️</div>

                <div class="stat-label">CURRENT LOCATION</div>

                <div class="stat-value">
                    <?php echo htmlspecialchars($player["current_location"]); ?>
                </div>

            </div>

        </div>

    </section>

    <!-- Update Username -->
    <section class="profile-card">

        <div class="profile-card-header">

            <div class="section-icon">✏️</div>

            <h2>Update Username</h2>

        </div>

        <form method="POST" class="profile-form">

            <p class="form-description">
                Choose a unique username between 3 and 30 characters.
            </p>

            <label for="username">New Username</label>

            <input
                type="text"
                id="username"
                name="username"
                value="<?php echo htmlspecialchars($player["username"]); ?>"
                minlength="3"
                maxlength="30"
                required
            >

            <button type="submit" name="update_username">
                ✏️ Update Username
            </button>

        </form>

    </section>

    <!-- Change Password -->
    <section class="profile-card">

        <div class="profile-card-header">

            <div class="section-icon">🔐</div>

            <h2>Change Password</h2>

        </div>

        <form method="POST" class="profile-form">

            <p class="form-description">
                Your new password must contain at least 8 characters.
            </p>

            <label for="current_password">Current Password</label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                placeholder="Enter current password"
                required
            >

            <label for="new_password">New Password</label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Enter new password"
                minlength="8"
                required
            >

            <label for="confirm_password">Confirm New Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm new password"
                minlength="8"
                required
            >

            <button type="submit" name="change_password">
                🔐 Change Password
            </button>

        </form>

    </section>

    <p class="profile-footer">
        Treasure Hunt: The Lost Island · Keep exploring! 🏝️
    </p>

</div>

</body>
</html>