<?php

require_once "slot.php";
require_once "const.php";

class SlotMachine
{
    private array $slots;
    private array $combination;

    public function __construct()
    {
        $this->slots = getSlotsParams();
        $this->combination = [];
    }

    public function getCombination(): array
    {
        return array_map(
            fn($slot) => $slot->name,
            $this->combination
        );
    }

    private function generateSlots(int $size): void
    {
        for ($cellIndex = 0; $cellIndex < $size; $cellIndex++) {
            $random = mt_rand() / mt_getrandmax();

            foreach ($this->slots as $slot) {
                if ($random < $slot->probability) {
                    $this->combination[] = $slot;
                    break;
                }
            }
        }
    }

    public function generateWinning(int $size, float $bet): float
    {
        $this->generateSlots($size);

        $prev = $this->combination[0]->name;

        foreach ($this->combination as $slot) {
            if ($prev === $slot->name) {
                $prev = $slot->name;
            } else {
                return 0.0;
            }
        }

        return $slot->ratio * $bet;
    }
}