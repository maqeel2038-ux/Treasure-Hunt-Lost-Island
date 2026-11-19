
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once "../config/database.php";

// Allow POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

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

$recoveryAmount = 20;
$recoveryCost = 30;
$maximumEnergy = 100;

try {

    $pdo->beginTransaction();

    // Get player information and lock the row
    $stmt = $pdo->prepare("
        SELECT energy, coins
        FROM players
        WHERE player_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        throw new Exception('Player account not found.');
    }

    $energy = (int)$player['energy'];
    $coins = (int)$player['coins'];

    // Check maximum energy
    if ($energy >= $maximumEnergy) {
        throw new Exception('Your energy is already full!');
    }

    // Check coins
    if ($coins < $recoveryCost) {
        throw new Exception('You need 30 coins to recover energy.');
    }

    // Calculate recovered energy
    $newEnergy = min(
        $energy + $recoveryAmount,
        $maximumEnergy
    );

    $actualRecovery = $newEnergy - $energy;

    // Deduct coins and restore energy
    $stmt = $pdo->prepare("
        UPDATE players
        SET energy = ?, coins = coins - ?
        WHERE player_id = ?
    ");

    $stmt->execute([
        $newEnergy,
        $recoveryCost,
        $playerId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Energy recovered successfully!',
        'energy' => $newEnergy,
        'coins' => $coins - $recoveryCost,
        'recovered' => $actualRecovery
    ]);

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

    exit;
}
?>
