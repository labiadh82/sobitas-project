<?php

namespace App\Services;

use App\Models\NumberSequence;

class NumberSequenceService
{
    /**
     * Get next number in format PREFIX-YYYY-NNNN (e.g. BL-2026-0001).
     */
    public function getNext(string $prefix): string
    {
        $year = (int) date('Y');
        $num = NumberSequence::getNextFor($prefix, $year);

        return sprintf('%s-%s-%s', $prefix, $year, str_pad((string) $num, 4, '0', STR_PAD_LEFT));
    }

    public function nextDevis(): string
    {
        return $this->getNext('DV');
    }

    public function nextBl(): string
    {
        return $this->getNext('BL');
    }

    public function nextFacture(): string
    {
        return $this->getNext('FA');
    }

    public function nextAvoir(): string
    {
        return $this->getNext('AV');
    }
}
