<?php

require_once "slot.php";

function getSlotsParams(): array {
    return [
        new Slot("seven", 0.1, 21),
        new Slot("cherry", 0.3, 7),
        new Slot("lemon", 0.55, 3),
        new Slot("bar", 1.0, 2),
    ];
}