<?php

require_once "database.php";
require_once "users.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Only POST is allowed"]);
    exit;
}

$body = json_decode(file_get_contents("php://input"), true);
$lockedIndexes = is_array($body) ? ($body["locked"] ?? []) : [];

if (!is_array($lockedIndexes)) {
    http_response_code(400);
    echo json_encode(["error" => "Locked slots must be an array"]);
    exit;
}

foreach ($lockedIndexes as $slotIndex) {
    if (!is_int($slotIndex) || $slotIndex < 0 || $slotIndex >= 3) {
        http_response_code(400);
        echo json_encode(["error" => "Locked slot index is invalid"]);
        exit;
    }
}

$lockedIndexes = array_values(array_unique($lockedIndexes));

if (count($lockedIndexes) > 2) {
    http_response_code(400);
    echo json_encode(["error" => "You can lock at most two slots"]);
    exit;
}

$user = getUser($pdo);
$pdo->beginTransaction();

try {
    $lockedUser = lockUser($pdo, $user["id"]);
    $lockState = decodeLockState($lockedUser["locked_slots"]);

    if (count($lockState["last"]) !== 3) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["error" => "Spin once before locking slots"]);
        exit;
    }

    if ($lockState["canLock"] === false) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["error" => "Spin once before locking slots again"]);
        exit;
    }

    $newLockedSlots = [];

    foreach ($lockedIndexes as $slotIndex) {
        $newLockedSlots[$slotIndex] = $lockState["last"][$slotIndex];
    }

    $newState = json_encode([
        "last" => $lockState["last"],
        "locked" => $newLockedSlots,
        "canLock" => true
    ], JSON_THROW_ON_ERROR);

    $stmt = $pdo->prepare(
        "UPDATE users
         SET locked_slots = CAST(:lock_state AS JSONB)
         WHERE id = :id"
    );
    $stmt->execute([
        "id" => $lockedUser["id"],
        "lock_state" => $newState
    ]);

    $pdo->commit();

    echo json_encode([
        "locked" => array_keys($newLockedSlots)
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $error;
}
