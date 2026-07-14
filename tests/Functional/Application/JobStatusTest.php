<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Application\AbstractJobStatusTest;

class JobStatusTest extends AbstractJobStatusTest
{
    use GetClientAdapterTrait;

    protected function getSelfUrl(): string
    {
        return 'https://results.example.com';
    }
}
