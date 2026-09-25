<?php

require_once "database.php";
require_once "users.php";
require_once "slotMachine.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "error" => "Only POST is allowed"
    ]);

    exit;
}

$body = json_decode(file_get_contents("php://input"), true);

if (!is_array($body) || !isset($body["bet"]) || !is_numeric($body["bet"])) {
    http_response_code(400);
    echo json_encode(["error" => "A valid bet is required"]);
    exit;
}

$bet = (float) $body["bet"];
$size = (int) ($body["size"] ?? 3);

if (
    $bet <= 0 ||
    $size !== 3 ||
    !is_finite($bet)
) {
    http_response_code(400);
    echo json_encode(["error" => "Bet or size is invalid"]);
    exit;
}

$user = getUser($pdo);

$pdo->beginTransaction();

try {
    $lockedUser = lockUser($pdo, $user["id"]);
    $lockState = decodeLockState($lockedUser["locked_slots"]);
    $lockedSlots = $lockState["locked"];
    $usedLocks = $lockedSlots !== [];
    $attemptNumber = (int) $lockedUser["attempts"] + 1;
    $isGuaranteedWin = $attemptNumber % 5 === 0;

    $slotMachine = new SlotMachine();
    $win = $isGuaranteedWin
        ? $slotMachine->generateGuaranteedWinning($size, $bet, $lockedSlots)
        : $slotMachine->generateWinning($size, $bet, $lockedSlots);
    $slots = $slotMachine->getCombination();

    $updatedUser = changeBalance(
        $pdo,
        $lockedUser["id"],
        $win - $bet,
        $slots,
        $usedLocks,
        $bet
    );

    if ($updatedUser === null) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["error" => "Insufficient balance"]);
        exit;
    }

    $pdo->commit();
} catch (InvalidArgumentException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(409);
    echo json_encode(["error" => $error->getMessage()]);
    exit;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $error;
}

http_response_code(200);

echo json_encode([
    "slots" => $slots,
    "win" => $win,
    "balance" => $updatedUser["balance"],
    "canLock" => !$usedLocks
]);