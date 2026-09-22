<?php

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

$slotMachine = new SlotMachine();
$win = $slotMachine->generateWinning($body["size"], $body["bet"]);
$slots = $slotMachine->getCombination();

http_response_code(200);

echo json_encode([
    "slots" => $slots,
    "win" => $win
]);