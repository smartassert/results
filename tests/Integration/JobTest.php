<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractJobTest;

class JobTest extends AbstractJobTest
{
    use GetResultsClientAdapterTrait;
}
