
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once "../config/database.php";

// Check login
if (empty($_SESSION['player_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in first.'
    ]);
    exit;
}

$playerId = $_SESSION['player_id'];

$baseReward = 50;

try {

    $pdo->beginTransaction();

    // Create reward record if it does not exist
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO daily_rewards (player_id)
        VALUES (?)
    ");
    $stmt->execute([$playerId]);

    // Lock reward record
    $stmt = $pdo->prepare("
        SELECT last_claim_date, login_streak, total_claims
        FROM daily_rewards
        WHERE player_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$playerId]);

    $reward = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reward) {
        throw new Exception('Could not retrieve daily reward.');
    }

    $today = new DateTimeImmutable('today');

    $lastClaim = !empty($reward['last_claim_date'])
        ? new DateTimeImmutable($reward['last_claim_date'])
        : null;

    // Prevent multiple claims on the same day
    if ($lastClaim && $lastClaim->format('Y-m-d') === $today->format('Y-m-d')) {
        throw new Exception('You have already claimed today\'s reward. Come back tomorrow!');
    }

    // Calculate login streak
    if ($lastClaim && $lastClaim->modify('+1 day')->format('Y-m-d') === $today->format('Y-m-d')) {
        $streak = (int)$reward['login_streak'] + 1;
    } else {
        $streak = 1;
    }

    // Bonus every 7 consecutive days
    $streakBonus = ($streak % 7 === 0) ? 50 : 0;

    $totalReward = $baseReward + $streakBonus;

    // Add coins to player
    $stmt = $pdo->prepare("
        UPDATE players
        SET coins = coins + ?
        WHERE player_id = ?
    ");
    $stmt->execute([$totalReward, $playerId]);

    // Update reward history
    $stmt = $pdo->prepare("
        UPDATE daily_rewards
        SET last_claim_date = ?,
            login_streak = ?,
            total_claims = total_claims + 1
        WHERE player_id = ?
    ");

    $stmt->execute([
        $today->format('Y-m-d'),
        $streak,
        $playerId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Daily reward claimed successfully!',
        'reward' => $totalReward,
        'base_reward' => $baseReward,
        'streak_bonus' => $streakBonus,
        'streak' => $streak
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>
