<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyRemoteEventRequestFactory\Factory;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Uid\Ulid;

abstract class AbstractConsumeWorkerEventRemoteEventTest extends AbstractApplicationTest
{
    private Factory $remoteEventRequestFactory;
    private JobRepository $jobRepository;
    private EventRepository $eventRepository;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $remoteEventRequestFactory = self::getContainer()->get(Factory::class);
        \assert($remoteEventRequestFactory instanceof Factory);
        $this->remoteEventRequestFactory = $remoteEventRequestFactory;

        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        $this->jobRepository = $jobRepository;

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);
        $this->eventRepository = $eventRepository;

        $jobLabel = (string) new Ulid();
        $response = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );
        self::assertSame(200, $response->getStatusCode());

        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);
        self::assertNotNull($job);
        $this->job = $job;

        self::assertSame(1, $this->jobRepository->count());
    }

    public function testConsumeSuccess(): void
    {
        self::assertSame(0, $this->eventRepository->count());

        $payload = [
            'job' => $this->job->getLabel(),
            'sequence_number' => 1,
            'type' => 'job/started',
            'label' => $this->job->getLabel(),
            'reference' => md5($this->job->getLabel()),
        ];

        $event = new RemoteEvent(
            name: 'worker.event',
            id: (string) new Ulid(),
            payload: $payload,
        );

        $response = $this->applicationClient->makeWorkerEventWebhookRequest(
            $this->remoteEventRequestFactory->createHeaders($event, $this->job->getToken()),
            $this->remoteEventRequestFactory->createBody($event, $this->job->getToken()),
        );

        self::assertSame(202, $response->getStatusCode());

        $eventEntities = $this->eventRepository->findAll();
        self::assertCount(1, $eventEntities);

        $eventEntity = $eventEntities[0];
        self::assertEquals($payload, $eventEntity->jsonSerialize());
    }

    public function testConsumeEventCreatedWithIncorrectToken(): void
    {
        self::assertSame(0, $this->eventRepository->count());

        $payload = [
            'job' => $this->job->getLabel(),
            'sequence_number' => 1,
            'type' => 'job/started',
            'label' => $this->job->getLabel(),
            'reference' => md5($this->job->getLabel()),
        ];

        $event = new RemoteEvent(
            name: 'worker.event',
            id: (string) new Ulid(),
            payload: $payload,
        );

        $response = $this->applicationClient->makeWorkerEventWebhookRequest(
            $this->remoteEventRequestFactory->createHeaders($event, 'incorrect-token'),
            $this->remoteEventRequestFactory->createBody($event, 'incorrect-token'),
        );

        $this->assertBadRequestResponse(
            $response,
            406,
            'unparseable_event',
            [
                'message' => 'Signature is wrong.',
            ],
        );
    }

    /**
     * @param callable(string): array<string, string> $payloadCreator
     * @param array<mixed>                            $expectedErrorPayload
     */
    #[DataProvider('consumeBadRequestPayloadDataProvider')]
    public function testConsumeBadRequestPayload(
        callable $payloadCreator,
        int $expectedStatusCode,
        string $expectedErrorType,
        array $expectedErrorPayload,
    ): void {
        self::assertSame(0, $this->eventRepository->count());

        $jobLabel = (string) new Ulid();

        $createJobResponse = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );

        self::assertSame(200, $createJobResponse->getStatusCode());

        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);
        self::assertNotNull($job);

        $payload = $payloadCreator($jobLabel);

        $event = new RemoteEvent(
            name: 'worker.event',
            id: (string) new Ulid(),
            payload: $payload,
        );

        $response = $this->applicationClient->makeWorkerEventWebhookRequest(
            $this->remoteEventRequestFactory->createHeaders($event, $job->getToken()),
            $this->remoteEventRequestFactory->createBody($event, $job->getToken()),
        );

        $this->assertBadRequestResponse(
            $response,
            $expectedStatusCode,
            $expectedErrorType,
            $expectedErrorPayload,
        );
    }

    /**
     * @return array<mixed>
     */
    public static function consumeBadRequestPayloadDataProvider(): array
    {
        return [
            'empty payload' => [
                'payloadCreator' => function () {
                    return [];
                },
                'expectedStatusCode' => 404,
                'expectedErrorType' => 'unparseable_event',
                'expectedErrorPayload' => [
                    'message' => 'Job "" not found.',
                ],
            ],
            'empty job' => [
                'payloadCreator' => function () {
                    return [
                        'job' => '',
                    ];
                },
                'expectedStatusCode' => 404,
                'expectedErrorType' => 'unparseable_event',
                'expectedErrorPayload' => [
                    'message' => 'Job "" not found.',
                ],
            ],
            'invalid job' => [
                'payloadCreator' => function () {
                    return [
                        'job' => 'invalid-job-label',
                    ];
                },
                'expectedStatusCode' => 404,
                'expectedErrorType' => 'unparseable_event',
                'expectedErrorPayload' => [
                    'message' => 'Job "invalid-job-label" not found.',
                ],
            ],
            'invalid sequence number' => [
                'payloadCreator' => function (string $jobLabel) {
                    return [
                        'job' => $jobLabel,
                        'sequence_number' => 'invalid-sequence-number',
                        'type' => 'job/started',
                        'label' => $jobLabel,
                        'reference' => md5($jobLabel),
                    ];
                },
                'expectedStatusCode' => 400,
                'expectedErrorType' => 'invalid_request',
                'expectedErrorPayload' => [
                    'sequence_number' => [
                        'value' => null,
                        'message' => sprintf(
                            'Required field "%s" invalid, missing from request or not a positive integer.',
                            'sequence_number'
                        ),
                    ],
                ],
            ],
            'invalid label' => [
                'payloadCreator' => function (string $jobLabel) {
                    return [
                        'job' => $jobLabel,
                        'sequence_number' => 1,
                        'type' => 'job/started',
                        'label' => '',
                        'reference' => md5($jobLabel),
                    ];
                },
                'expectedStatusCode' => 400,
                'expectedErrorType' => 'invalid_request',
                'expectedErrorPayload' => [
                    'label' => [
                        'value' => null,
                        'message' => 'Required field "label" invalid, missing from request or not a string.',
                    ],
                ],
            ],
            'invalid reference' => [
                'payloadCreator' => function (string $jobLabel) {
                    return [
                        'job' => $jobLabel,
                        'sequence_number' => 1,
                        'type' => 'job/started',
                        'label' => $jobLabel,
                        'reference' => '',
                    ];
                },
                'expectedStatusCode' => 400,
                'expectedErrorType' => 'invalid_request',
                'expectedErrorPayload' => [
                    'reference' => [
                        'value' => null,
                        'message' => 'Required field "reference" invalid, missing from request or not a string.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $expectedPayload
     */
    private function assertBadRequestResponse(
        ResponseInterface $response,
        int $expectedStatusCode,
        string $expectedErrorType,
        array $expectedPayload,
    ): void {
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertEquals(
            [
                'error' => [
                    'type' => $expectedErrorType,
                    'payload' => $expectedPayload,
                ],
            ],
            $responseData,
        );
    }
}
