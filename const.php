<?php

require "slot.php";

function getSlotsParams(): array {
    return [
        new Slot("seven", 0.05, 7),
        new Slot("cherry", 0.15, 4),
        new Slot("bell", 0.3, 2),
        new Slot("bar", 0.5, 1.5),
    ];
}