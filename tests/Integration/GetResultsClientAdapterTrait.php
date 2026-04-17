<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\ResultsClientAdapter;
use SmartAssert\SymfonyTestClient\ClientInterface;

trait GetResultsClientAdapterTrait
{
    protected function getClientAdapter(): ClientInterface
    {
        $resultsClientAdapter = self::getContainer()->get(ResultsClientAdapter::class);
        \assert($resultsClientAdapter instanceof ResultsClientAdapter);

        return $resultsClientAdapter;
    }
}
