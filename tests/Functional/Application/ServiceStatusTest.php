<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Application\AbstractServiceStatusTest;

class ServiceStatusTest extends AbstractServiceStatusTest
{
    use GetClientAdapterTrait;

    protected function getExpectedReadyValue(): bool
    {
        return true;
    }
}
