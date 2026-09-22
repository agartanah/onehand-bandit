<?php

require_once "slot.php";

function getSlotsParams(): array {
    return [
        new Slot("seven", 0.05, 7),
        new Slot("cherry", 0.2, 4),
        new Slot("lemon", 0.5, 2),
        new Slot("bar", 1.0, 1.5),
    ];
}