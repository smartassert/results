<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Request\AddEvent\Request;
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
            !array_key_exists(Request::KEY_BODY, $expectedSerializedEvent)
            && array_key_exists(Request::KEY_BODY, $serializedEvent)
        ) {
            unset($serializedEvent[Request::KEY_BODY]);
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
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 1,
                    Request::KEY_TYPE => 'job/compiled',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                ],
                'expectedSerializedEvent' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 1,
                    Request::KEY_TYPE => 'job/compiled',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                ],
            ],
            'body empty, related references empty' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 2,
                    Request::KEY_TYPE => 'job/compiled',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_RELATED_REFERENCES => [],
                    Request::KEY_BODY => [],
                ],
                'expectedSerializedEvent' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 2,
                    Request::KEY_TYPE => 'job/compiled',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_BODY => [],
                ],
            ],
            'body not empty, related references empty' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_BODY => [
                        'tests' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                        ],
                    ],
                ],
                'expectedSerializedEvent' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_BODY => [
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
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_RELATED_REFERENCES => [
                        [
                            'invalid-key' => 'value',
                        ],
                    ],
                ],
                'expectedSerializedEvent' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                ],
            ],
            'related references valid' => [
                'jobLabel' => $jobLabel,
                'requestPayload' => [
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_RELATED_REFERENCES => [
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
                    Request::KEY_JOB => $jobLabel,
                    Request::KEY_SEQUENCE_NUMBER => 3,
                    Request::KEY_TYPE => 'job/started',
                    Request::KEY_LABEL => $jobLabel,
                    Request::KEY_REFERENCE => md5($jobLabel),
                    Request::KEY_RELATED_REFERENCES => [
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
        $firstRequestPayload[Request::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $firstResponse = $this->applicationClient->makeEventAddRequest($addEventUrl, $firstRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        $secondRequestPayload[Request::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $secondResponse = $this->applicationClient->makeEventAddRequest($addEventUrl, $secondRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function addIsIdempotentDataProvider(): array
    {
        $jobLabel1 = (string) new Ulid();
        $jobLabel2 = (string) new Ulid();
        $jobLabel3 = (string) new Ulid();
        $jobLabel4 = (string) new Ulid();

        return [
            'type is not modified by second request' => [
                'jobLabel' => $jobLabel1,
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    Request::KEY_JOB => $jobLabel1,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    Request::KEY_JOB => $jobLabel1,
                    Request::KEY_TYPE => 'second request type',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'label is not modified by second request' => [
                'jobLabel' => $jobLabel2,
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    Request::KEY_JOB => $jobLabel2,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    Request::KEY_JOB => $jobLabel2,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_REFERENCE => 'second request reference',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'reference is not modified by second request' => [
                'jobLabel' => $jobLabel3,
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    Request::KEY_JOB => $jobLabel3,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    Request::KEY_JOB => $jobLabel3,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'second request reference',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'body is not modified by second request' => [
                'jobLabel' => $jobLabel4,
                'sequenceNumber' => rand(),
                'firstRequestPayload' => [
                    Request::KEY_JOB => $jobLabel4,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    Request::KEY_JOB => $jobLabel4,
                    Request::KEY_TYPE => 'first request type',
                    Request::KEY_LABEL => 'first request label',
                    Request::KEY_REFERENCE => 'first request reference',
                    Request::KEY_BODY => [
                        'second request key' => 'second request value',
                    ],
                ],
            ],
        ];
    }

    private function createJobAddEventUrl(string $jobLabel): string
    {
        $createJobResponse = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );

        $responseData = json_decode($createJobResponse->getBody()->getContents(), true);
        \assert(is_array($responseData));
        \assert(array_key_exists('event_add_url', $responseData));

        return (string) $responseData['event_add_url'];
    }
}
