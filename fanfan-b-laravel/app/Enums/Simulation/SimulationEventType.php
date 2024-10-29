<?php

namespace App\Enums\Simulation;

use BenSampo\Enum\Enum;

final class SimulationEventType extends Enum
{
    const GOAL = 'goal'; // custom
    const FOUL = 'foul';
    const FOUL_FREE = 'foul_free';
    const FOUL_Y_FREE = 'foul_y_free';
    const FOUL_R_FREE = 'foul_r_free';
    const PK = 'pk';
    const FOUL_PK = 'foul_pk';
    const FOUL_Y_PK = 'foul_y_pk';
    const FOUL_R_PK = 'foul_r_pk';
    const PASS = 'pass';
    const PASS_COMM = 'pass_comm'; // commentary
    const CROSS = 'cross';
    const SHOT = 'shot';
    const CORNERKICK = 'cornerkick';
    const ASSIST = 'assist';
    const SUBSTITUTE = 'substitute';
}
