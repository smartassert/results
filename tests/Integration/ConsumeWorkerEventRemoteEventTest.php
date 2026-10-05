<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractConsumeWorkerEventRemoteEventTest;

class ConsumeWorkerEventRemoteEventTest extends AbstractConsumeWorkerEventRemoteEventTest
{
    use GetHttpAdapter;
}
