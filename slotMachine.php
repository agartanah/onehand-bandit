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

    private function generateRandomSlot(): Slot
    {
        $random = mt_rand() / mt_getrandmax();

        foreach ($this->slots as $slot) {
            if ($random < $slot->probability) {
                return $slot;
            }
        }

        return $this->slots[array_key_last($this->slots)];
    }

    private function findSlot(string $name): Slot
    {
        foreach ($this->slots as $slot) {
            if ($slot->name === $name) {
                return $slot;
            }
        }

        throw new InvalidArgumentException("Unknown slot: {$name}");
    }

    private function generateSlots(int $size, array $lockedSlots = []): void
    {
        for ($cellIndex = 0; $cellIndex < $size; $cellIndex++) {
            if (
                array_key_exists($cellIndex, $lockedSlots) &&
                $lockedSlots[$cellIndex] !== null
            ) {
                $this->combination[] = $this->findSlot($lockedSlots[$cellIndex]);
                continue;
            }

            $this->combination[] = $this->generateRandomSlot();
        }
    }

    public function generateWinning(
        int $size,
        float $bet,
        array $lockedSlots = []
    ): float
    {
        $this->generateSlots($size, $lockedSlots);

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

    public function generateGuaranteedWinning(
        int $size,
        float $bet,
        array $lockedSlots = []
    ): float
    {
        $lockedNames = array_values(
            array_filter($lockedSlots, fn($slotName) => $slotName !== null)
        );
        $winningSlot = $this->generateRandomSlot();

        if ($lockedNames !== []) {
            $winningSlot = $this->findSlot($lockedNames[0]);

            foreach ($lockedNames as $lockedName) {
                if ($lockedName !== $winningSlot->name) {
                    throw new InvalidArgumentException(
                        "Locked slots must have the same symbol on a guaranteed spin"
                    );
                }
            }
        }

        for ($cellIndex = 0; $cellIndex < $size; $cellIndex++) {
            $this->combination[] = $winningSlot;
        }

        return $winningSlot->ratio * $bet;
    }
}