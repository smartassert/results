<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Component\Uid\Ulid;

abstract class AbstractJobStatusTest extends AbstractApplicationTest
{
    public function testStatusSuccess(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $jobLabel,
            'POST'
        );

        $job = $jobRepository->findAll()[0];
        self::assertInstanceOf(Job::class, $job);

        $response = $this->applicationClient->makeJobRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $jobLabel,
            'GET'
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertArrayHasKey('state', $responseData);
        self::assertSame('awaiting-events', $responseData['state']);
    }
}
