<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractJobStatusTest;

class JobStatusTest extends AbstractJobStatusTest
{
    use GetResultsClientAdapterTrait;

    protected function getSelfUrl(): string
    {
        return 'https://localhost';
    }
}
