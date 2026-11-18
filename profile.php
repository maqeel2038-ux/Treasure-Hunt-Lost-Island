
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

    <style>
        .profile-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-heading {
            margin-bottom: 30px;
        }

        .profile-heading h1 {
            color: #FFC629;
            font-size: 30px;
        }

        .profile-heading p {
            color: #c5cde0;
        }

        .profile-card {
            background: #263A5D;
            border: 1px solid rgba(255,198,41,0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .profile-card h2 {
            color: #FFC629;
            margin-bottom: 20px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .profile-info div {
            background: #101D38;
            padding: 18px;
            border-radius: 10px;
        }

        .profile-info span {
            display: block;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .profile-info strong {
            color: white;
            overflow-wrap: anywhere;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 500px;
        }

        .profile-form label {
            color: white;
        }

        .profile-form input {
            padding: 13px;
            border-radius: 8px;
            border: 1px solid #526586;
            background: #101D38;
            color: white;
            font-size: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        .profile-form button {
            padding: 13px;
            background: #FFC629;
            color: #101D38;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        .profile-form button:hover {
            background: #e6ad13;
        }

        .profile-message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .profile-message.success {
            background: rgba(40,167,69,0.15);
            color: #65e58b;
        }

        .profile-message.error {
            background: rgba(220,53,69,0.15);
            color: #ff7777;
        }

        .profile-back {
            display: inline-block;
            color: #FFC629;
            text-decoration: none;
            margin-bottom: 25px;
        }

        @media (max-width: 600px) {
            .profile-info {
                grid-template-columns: 1fr;
            }

            .profile-container {
                padding: 15px;
                margin-top: 20px;
            }

            .profile-heading h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="profile-container">

    <a href="dashboard.php" class="profile-back">
        ← Back to Dashboard
    </a>

    <div class="profile-heading">
        <h1>👤 Player Profile</h1>
        <p>Manage your explorer account and personal information.</p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="profile-message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Player Information -->
    <section class="profile-card">

        <h2>👤 Account Information</h2>

        <div class="profile-info">

            <div>
                <span>PLAYER ID</span>
                <strong><?php echo (int)$player["player_id"]; ?></strong>
            </div>

            <div>
                <span>USERNAME</span>
                <strong><?php echo htmlspecialchars($player["username"]); ?></strong>
            </div>

            <div>
                <span>EMAIL ADDRESS</span>
                <strong><?php echo htmlspecialchars($player["email"]); ?></strong>
            </div>

            <div>
                <span>REGISTRATION DATE</span>
                <strong>
                    <?php echo date("d M Y", strtotime($player["created_at"])); ?>
                </strong>
            </div>

        </div>

    </section>

    <!-- Game Statistics -->
    <section class="profile-card">

        <h2>🏆 Explorer Statistics</h2>

        <div class="profile-info">

            <div>
                <span>GOLD COINS</span>
                <strong>🪙 <?php echo (int)$player["coins"]; ?></strong>
            </div>

            <div>
                <span>TOTAL SCORE</span>
                <strong>🏆 <?php echo (int)$player["score"]; ?></strong>
            </div>

            <div>
                <span>ENERGY</span>
                <strong>⚡ <?php echo (int)$player["energy"]; ?>%</strong>
            </div>

            <div>
                <span>CURRENT LOCATION</span>
                <strong><?php echo htmlspecialchars($player["current_location"]); ?></strong>
            </div>

        </div>

    </section>

    <!-- Update Username -->
    <section class="profile-card">

        <h2>✏️ Update Username</h2>

        <form method="POST" class="profile-form">

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
                Update Username
            </button>

        </form>

    </section>

    <!-- Change Password -->
    <section class="profile-card">

        <h2>🔐 Change Password</h2>

        <form method="POST" class="profile-form">

            <label for="current_password">Current Password</label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                required
            >

            <label for="new_password">New Password</label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                minlength="8"
                required
            >

            <label for="confirm_password">Confirm New Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <button type="submit" name="change_password">
                Change Password
            </button>

        </form>

    </section>

</div>

</body>
</html>
