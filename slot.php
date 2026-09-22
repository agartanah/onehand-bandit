<?php

class Slot
{
    public string $name;
    public float $probability;
    public float $ratio;

    public function __construct(
        string $name,
        float $probability,
        float $ratio
    ) {
        $this->name = $name;
        $this->probability = $probability;
        $this->ratio = $ratio;
    }
}