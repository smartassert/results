<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Request\AddEventRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractAddEventTest extends AbstractApplicationTest
{
    private EventRepository $eventRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);
        $this->eventRepository = $eventRepository;
    }

    /**
     * @param non-empty-string                   $jobLabel
     * @param array<string, array<mixed>|string> $requestPayload
     * @param array<mixed>                       $expectedSerializedEvent
     */
    #[DataProvider('addSuccessDataProvider')]
    public function testAddSuccess(string $jobLabel, array $requestPayload, array $expectedSerializedEvent): void
    {
        self::assertSame(0, $this->eventRepository->count([]));

        $addEventUrl = $this->createJobAddEventUrl($jobLabel);
        $response = $this->applicationClient->makeEventAddRequest($addEventUrl, $requestPayload);

        self::assertSame(200, $response->getStatusCode());

        $event = $this->eventRepository->findAll()[0];
        $serializedEvent = $event->jsonSerialize();

        if (
            !array_key_exists(AddEventRequest::KEY_BODY, $expectedSerializedEvent)
            && array_key_exists(AddEventRequest::KEY_BODY, $serializedEvent)
        ) {
            unset($serializedEvent[AddEventRequest::KEY_BODY]);
        }

        self::assertEquals($expectedSerializedEvent, $serializedEvent);
    }

    /**
     * @return array<mixed>
     */
    public static function addSuccessDataProvider(): array
    {
        $jobLabel = (string) new Ulid();

        return [
            'body not present, related references not present' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 1,
                    AddEventRequest::KEY_TYPE => 'job/compiled',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                ],
                'expectedSerializedEvent' => [
                    'job' => $jobLabel,
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 1,
                    AddEventRequest::KEY_TYPE => 'job/compiled',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                ],
            ],
            'body empty, related references empty' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 2,
                    AddEventRequest::KEY_TYPE => 'job/compiled',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_RELATED_REFERENCES => [],
                    AddEventRequest::KEY_BODY => [],
                ],
                'expectedSerializedEvent' => [
                    'job' => $jobLabel,
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 2,
                    AddEventRequest::KEY_TYPE => 'job/compiled',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_BODY => [],
                ],
            ],
            'body not empty, related references empty' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_BODY => [
                        'tests' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ],
                    ],
                ],
                'expectedSerializedEvent' => [
                    'job' => $jobLabel,
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_BODY => [
                        'tests' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ],
                    ],
                ],
            ],
            'related references invalid' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_RELATED_REFERENCES => [
                        [
                            'invalid-key' => 'value',
                        ],
                    ],
                ],
                'expectedSerializedEvent' => [
                    'job' => $jobLabel,
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                ],
            ],
            'related references valid' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_RELATED_REFERENCES => [
                        [
                            'label' => 'reference 1 label',
                            'reference' => 'reference 1 reference',
                        ],
                        [
                            'label' => 'reference 2 label',
                            'reference' => 'reference 2 reference',
                        ],
                    ],
                ],
                'expectedSerializedEvent' => [
                    'job' => $jobLabel,
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                    AddEventRequest::KEY_TYPE => 'job/started',
                    AddEventRequest::KEY_LABEL => $jobLabel,
                    AddEventRequest::KEY_REFERENCE => md5($jobLabel),
                    AddEventRequest::KEY_RELATED_REFERENCES => [
                        [
                            'label' => 'reference 1 label',
                            'reference' => 'reference 1 reference',
                        ],
                        [
                            'label' => 'reference 2 label',
                            'reference' => 'reference 2 reference',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *     header: array{type: string, reference: string, label: string},
     *     body: array<mixed>
     * } $firstRequestPayload
     * @param array{
     *     header: array{type: string, reference: string, label: string},
     *     body: array<mixed>
     * } $secondRequestPayload
     */
    #[DataProvider('addIsIdempotentDataProvider')]
    public function testAddIsIdempotent(
        string $jobLabel,
        int $sequenceNumber,
        array $firstRequestPayload,
        array $secondRequestPayload
    ): void {
        self::assertSame(0, $this->eventRepository->count([]));

        $addEventUrl = $this->createJobAddEventUrl($jobLabel);
        $firstRequestPayload[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $firstResponse = $this->applicationClient->makeEventAddRequest($addEventUrl, $firstRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        $secondRequestPayload[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $secondResponse = $this->applicationClient->makeEventAddRequest($addEventUrl, $secondRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function addIsIdempotentDataProvider(): array
    {
        return [
            'type is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'second request type',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'label is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_REFERENCE => 'second request reference',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'reference is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'second request reference',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'body is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_BODY => [
                        'second request key' => 'second request value',
                    ],
                ],
            ],
        ];
    }

    private function createJobAddEventUrl(string $jobLabel): string
    {
        $createJobResponse = $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'POST'
        );

        $responseData = json_decode($createJobResponse->getBody()->getContents(), true);
        \assert(is_array($responseData));
        \assert(array_key_exists('event_add_url', $responseData));

        return (string) $responseData['event_add_url'];
    }
}
