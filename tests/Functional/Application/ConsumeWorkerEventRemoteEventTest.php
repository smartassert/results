<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Application\AbstractConsumeWorkerEventRemoteEventTest;

class ConsumeWorkerEventRemoteEventTest extends AbstractConsumeWorkerEventRemoteEventTest
{
    use GetClientAdapterTrait;
}
