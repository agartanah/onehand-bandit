<?php

function getUser(PDO $pdo): array
{
    $userId = $_COOKIE["user_id"] ?? null;

    if ($userId !== null && preg_match(
        "/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i",
        $userId
    )) {
        $stmt = $pdo->prepare(
            "SELECT id, balance, attempts, locked_slots
             FROM users
             WHERE id = :id"
        );
        $stmt->execute(["id" => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user !== false) {
            return $user;
        }
    }

    $stmt = $pdo->query(
        "INSERT INTO users DEFAULT VALUES
         RETURNING id, balance, attempts, locked_slots"
    );
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    setcookie("user_id", $user["id"], [
        "expires" => time() + 60 * 60 * 24 * 365,
        "httponly" => true,
        "path" => "/",
        "samesite" => "Lax"
    ]);

    return $user;
}

function lockUser(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT id, balance, attempts, locked_slots
         FROM users
         WHERE id = :id
         FOR UPDATE"
    );
    $stmt->execute(["id" => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        throw new RuntimeException("User not found");
    }

    return $user;
}

function decodeLockState(?string $encodedState): array
{
    if ($encodedState === null || $encodedState === "") {
        return ["last" => [], "locked" => [], "canLock" => true];
    }

    $state = json_decode($encodedState, true);

    if (!is_array($state)) {
        return ["last" => [], "locked" => [], "canLock" => true];
    }

    return [
        "last" => is_array($state["last"] ?? null) ? $state["last"] : [],
        "locked" => is_array($state["locked"] ?? null) ? $state["locked"] : [],
        "canLock" => $state["canLock"] ?? true
    ];
}

function changeBalance(
    PDO $pdo,
    string $userId,
    float $amount,
    array $slots,
    bool $usedLocks,
    float $bet
): ?array
{
    $lockState = json_encode([
        "last" => $slots,
        "locked" => [],
        "canLock" => !$usedLocks
    ], JSON_THROW_ON_ERROR);

    $stmt = $pdo->prepare(
        "UPDATE users
         SET balance = balance + CAST(:amount AS NUMERIC),
             attempts = attempts + 1,
             locked_slots = CAST(:lock_state AS JSONB)
         WHERE id = :id
                     AND balance >= CAST(:bet AS NUMERIC)
         RETURNING id, balance, attempts"
    );
    $stmt->execute([
        "id" => $userId,
        "amount" => $amount,
        "bet" => $bet,
        "lock_state" => $lockState
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user === false ? null : $user;
}

