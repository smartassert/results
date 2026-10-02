<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Repository\JobRepository;
use SmartAssert\SymfonyRemoteEventRequestFactory\Factory;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Uid\Ulid;

abstract class AbstractConsumeWorkerEventRemoteEventTest extends AbstractApplicationTest
{
    private Factory $remoteEventRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $remoteEventRequestFactory = self::getContainer()->get(Factory::class);
        \assert($remoteEventRequestFactory instanceof Factory);
        $this->remoteEventRequestFactory = $remoteEventRequestFactory;
    }

    public function testCreateSuccess(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        self::assertSame(0, $jobRepository->count());

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);
        self::assertSame(0, $eventRepository->count());

        $jobLabel = (string) new Ulid();

        $response = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );

        $job = $jobRepository->findOneBy(['label' => $jobLabel]);
        self::assertNotNull($job);

        self::assertSame(200, $response->getStatusCode());

        $payload = [
            'job' => $job->getLabel(),
            'sequence_number' => 1,
            'type' => 'job/started',
            'label' => $jobLabel,
            'reference' => md5($jobLabel),
        ];

        $event = new RemoteEvent(
            name: 'worker.event',
            id: (string) new Ulid(),
            payload: $payload,
        );

        $response = $this->applicationClient->makeWorkerEventWebhookRequest(
            $this->remoteEventRequestFactory->createHeaders($event, $job->getToken()),
            $this->remoteEventRequestFactory->createBody($event, $job->getToken()),
        );

        self::assertSame(202, $response->getStatusCode());

        $eventEntities = $eventRepository->findAll();
        self::assertCount(1, $eventEntities);

        $eventEntity = $eventEntities[0];
        self::assertEquals($payload, $eventEntity->jsonSerialize());
    }
}
