<?php

namespace App\Enums\Simulation;

use BenSampo\Enum\Enum;

final class SimulationEndingType extends Enum
{
    const BLOCKED = 'blocked';
    const FIRST_HALF_END = 'first_half_end';
    const FIRST_HALF_START = 'first_half_start';
    const GOAL = 'goal';
    const HITWOODWORK = 'hitwoodwork';
    const OFFSIDE = 'offside';
    const OUT = 'out';
    const PASS = 'pass';
    const SAVED = 'saved';
    const SECOND_HALF_START = 'second_half_start';
    const SECOND_HALF_END = 'second_half_end';
}
