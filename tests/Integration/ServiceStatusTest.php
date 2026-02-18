<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractServiceStatusTest;

class ServiceStatusTest extends AbstractServiceStatusTest
{
    use GetHttpAdapter;

    protected function getExpectedReadyValue(): bool
    {
        return false;
    }
}
