<?php

namespace App\Enums\Simulation;

use BenSampo\Enum\Enum;

final class SimulationCommentType extends Enum
{
    // type:event
    const SHOT_CORNERKICK = 'shot_cornerkick';
    const SHOT_CROSS = 'shot_cross';
    const FOUL_FREE = 'foul_free';
    const FOUL_PK = 'foul_pk';
    const FOUL_Y_FREE = 'foul_y_free';
    const FOUL_Y_PK = 'foul_y_pk';
    const FOUL_R_FREE = 'foul_r_free';
    const FOUL_R_PK = 'foul_r_pk';
    const FOUL = 'foul';
    const SHOT = 'shot';
    const PASS_COMM = 'pass_comm';

    // type:ending
    const PASS = 'pass';
    const OFFSIDE = 'offside';
    const SAVED = 'saved';
    const OUT = 'out';
    const BLOCKED = 'blocked';
    const HITWOODWORK = 'hitwoodwork';
    const GOAL = 'goal';
    const FIRST_HALF_START = 'first_half_start';
    const FIRST_HALF_END = 'first_half_end';
    const SECOND_HALF_START = 'second_half_start';
    const SECOND_HALF_END = 'second_half_end';

    // custom
    const SUBSTITUTE = 'substitute';
}
