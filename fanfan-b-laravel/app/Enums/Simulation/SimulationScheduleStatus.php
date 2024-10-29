<?php

namespace App\Enums\Simulation;

use BenSampo\Enum\Enum;

final class SimulationScheduleStatus extends Enum
{
    const FIXTURE = 'Fixture';
    const PLAYED = 'Played';
    const PLAYING = 'Playing';
}
