<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationClient\HttpResponseFactory;
use App\Tests\Services\ApplicationClient\ResultsClientAdapter;
use SmartAssert\ResultsClient\Client as ResultsClient;
use SmartAssert\SymfonyTestClient\ClientInterface;

trait GetResultsClientAdapterTrait
{
    protected function getClientAdapter(): ClientInterface
    {
        $resultsClient = self::getContainer()->get(ResultsClient::class);
        \assert($resultsClient instanceof ResultsClient);

        return new ResultsClientAdapter($resultsClient, new HttpResponseFactory());
    }
}
