<?php

require_once "database.php";
require_once "users.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "error" => "Only GET is allowed"
    ]);

    exit;
}

$user = getUser($pdo);

$lockState = decodeLockState($user["locked_slots"] ?? null);

echo json_encode([
    "id" => $user["id"],
    "balance" => $user["balance"],
    "attempts" => $user["attempts"],
    "canLock" => $lockState["canLock"]
]);