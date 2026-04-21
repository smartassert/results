<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Component\Uid\Ulid;

abstract class AbstractJobCreationTest extends AbstractApplicationTest
{
    public function testCreateSuccess(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $response = $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'POST'
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $jobRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('label', $responseData);
        self::assertSame($jobLabel, $responseData['label']);

        $job = $jobRepository->findOneBy(['label' => $responseData['label']]);
        self::assertInstanceOf(Job::class, $job);

        self::assertEquals(
            [
                'label' => $jobLabel,
                'event_add_url' => $this->getSelfUrl() . '/event/add/' . $job->getToken(),
                'state' => 'awaiting-events',
                'meta_state' => [
                    'ended' => false,
                    'succeeded' => false,
                ],
            ],
            $responseData,
        );
    }

    public function testCreateIsIdempotent(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'POST'
        );
        self::assertSame(1, $jobRepository->count([]));

        $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'POST'
        );
        self::assertSame(1, $jobRepository->count([]));
    }

    abstract protected function getSelfUrl(): string;
}
