<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Request\AddEventRequest;
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
     * @dataProvider addBadMethodDataProvider
     */
    public function testAddBadMethod(string $method): void
    {
        $response = $this->applicationClient->makeEventAddRequest((string) new Ulid(), [], $method);

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function addBadMethodDataProvider(): array
    {
        return [
            'GET' => [
                'method' => 'GET',
            ],
            'PUT' => [
                'method' => 'PUT',
            ],
            'DELETE' => [
                'method' => 'DELETE',
            ],
        ];
    }

    /**
     * @dataProvider addBadRequestDataProvider
     *
     * @param array<string, array<mixed>|string> $requestPayload
     * @param array<mixed>                       $expectedResponseData
     */
    public function testAddBadRequest(array $requestPayload, array $expectedResponseData): void
    {
        self::assertSame(0, $this->eventRepository->count([]));

        $jobToken = $this->createJobToken((string) new Ulid());

        $response = $this->applicationClient->makeEventAddRequest($jobToken, $requestPayload);

        self::assertSame(0, $this->eventRepository->count([]));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertSame($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function addBadRequestDataProvider(): array
    {
        $expectedInvalidSequenceNumberResponseData = [
            'error' => [
                'type' => 'invalid_request',
                'payload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => [
                        'value' => null,
                        'message' => 'Required field "sequence_number" invalid, '
                            . 'missing from request or not a positive integer.',
                    ],
                ],
            ],
        ];

        return [
            'sequence number missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number not an integer' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 'not an integer',
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number is zero' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 0,
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number is negative' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => -1,
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'type missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_TYPE => [
                                'value' => null,
                                'message' => 'Required field "type" invalid, missing from request or not a string.',
                            ],
                        ],
                    ],
                ],
            ],
            'label missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_LABEL => [
                                'value' => null,
                                'message' => 'Required field "label" invalid, missing from request or not a string.',
                            ],
                        ],
                    ],
                ],
            ],
            'reference missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                    AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_REFERENCE => [
                                'value' => null,
                                'message' => 'Required field "reference" invalid, '
                                    . 'missing from request or not a string.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider addSuccessDataProvider
     *
     * @param non-empty-string                   $jobLabel
     * @param array<string, array<mixed>|string> $requestPayload
     * @param array<mixed>                       $expectedSerializedEvent
     */
    public function testAddSuccess(string $jobLabel, array $requestPayload, array $expectedSerializedEvent): void
    {
        $jobToken = $this->createJobToken($jobLabel);

        self::assertSame(0, $this->eventRepository->count([]));

        $response = $this->applicationClient->makeEventAddRequest($jobToken, $requestPayload);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $this->eventRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertEquals($expectedSerializedEvent, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public function addSuccessDataProvider(): array
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
     * @dataProvider addIsIdempotentDataProvider
     *
     * @param array{
     *     header: array{type: string, reference: string, label: string},
     *     body: array<mixed>
     * } $firstRequestPayload
     * @param array{
     *     header: array{type: string, reference: string, label: string},
     *     body: array<mixed>
     * } $secondRequestPayload
     */
    public function testAddIsIdempotent(
        string $jobLabel,
        int $sequenceNumber,
        array $firstRequestPayload,
        array $secondRequestPayload
    ): void {
        $jobToken = $this->createJobToken($jobLabel);

        self::assertSame(0, $this->eventRepository->count([]));

        $firstRequestPayload[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $firstResponse = $this->applicationClient->makeEventAddRequest($jobToken, $firstRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        $secondRequestPayload[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;

        $secondResponse = $this->applicationClient->makeEventAddRequest($jobToken, $secondRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public function addIsIdempotentDataProvider(): array
    {
        return [
            'type is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
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
                'sequence_number' => rand(),
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
                'sequence_number' => rand(),
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
                'sequence_number' => rand(),
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

    private function createJobToken(string $jobLabel): string
    {
        $createJobResponse = $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'POST'
        );

        $createTokenResponseData = json_decode($createJobResponse->getBody()->getContents(), true);
        \assert(is_array($createTokenResponseData));
        \assert(array_key_exists('token', $createTokenResponseData));

        return (string) $createTokenResponseData['token'];
    }
}
