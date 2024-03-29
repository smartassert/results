<?php

declare(strict_types=1);

namespace App\Enum;

enum JobState: string
{
    case AWAITING_EVENTS = 'awaiting-events';
    case STARTED = 'started';
    case COMPILING = 'compiling';
    case COMPILED = 'compiled';
    case EXECUTING = 'executing';
    case EXECUTED = 'executed';
    case ENDED = 'ended';
}
