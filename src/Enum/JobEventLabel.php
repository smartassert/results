<?php

declare(strict_types=1);

namespace App\Enum;

enum JobEventLabel: string
{
    case STARTED = 'job/started';

    case COMPILATION_STARTED = 'job/compilation/started';

    case COMPILATION_ENDED = 'job/compilation/ended';

    case EXECUTION_STARTED = 'job/execution/started';

    case EXECUTION_ENDED = 'job/execution/ended';

    case ENDED = 'job/ended';
}
