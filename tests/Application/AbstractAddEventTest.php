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
        $response = $this->applicationClient->makeAddEventRequest((string) new Ulid(), [], $method);

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
            'HEAD' => [
                'method' => 'HEAD',
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

        $token = $this->createToken((string) new Ulid());

        $response = $this->applicationClient->makeAddEventRequest($token, $requestPayload);

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
                        'message' => 'Required field "sequence_number" invalid, ' .
                            'missing from request or not a positive integer.',
                    ],
                ],
            ],
        ];

        return [
            'sequence number missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number not an integer' => [
                'requestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 'not an integer',
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number is zero' => [
                'requestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 0,
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'sequence number is negative' => [
                'requestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => -1,
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => $expectedInvalidSequenceNumberResponseData,
            ],
            'type missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
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
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => 'reference_' . md5((string) rand()),
                    ],
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
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                        AddEventRequest::KEY_TYPE => 'type_' . md5((string) rand()),
                        AddEventRequest::KEY_LABEL => 'label_' . md5((string) rand()),
                    ],
                    AddEventRequest::KEY_BODY => json_encode([]),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_REFERENCE => [
                                'value' => null,
                                'message' => 'Required field "reference" invalid, ' .
                                    'missing from request or not a string.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testAddSuccess(): void
    {
        $jobLabel = (string) new Ulid();
        $token = $this->createToken($jobLabel);

        self::assertSame(0, $this->eventRepository->count([]));

        $sequenceNumber = rand(1, 100);
        $type = md5((string) rand());
        $label = md5((string) rand());
        $reference = md5((string) rand());
        $body = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key3.1' => 'value3.1',
                'key3.2' => 'value3.2',
            ],
        ];

        $requestPayload = [
            AddEventRequest::KEY_HEADER_SECTION => [
                AddEventRequest::KEY_SEQUENCE_NUMBER => $sequenceNumber,
                AddEventRequest::KEY_TYPE => $type,
                AddEventRequest::KEY_LABEL => $label,
                AddEventRequest::KEY_REFERENCE => $reference,
            ],
            AddEventRequest::KEY_BODY => $body,
        ];

        $response = $this->applicationClient->makeAddEventRequest($token, $requestPayload);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $this->eventRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertEquals(
            [
                'sequence_number' => $sequenceNumber,
                'job' => $jobLabel,
                AddEventRequest::KEY_TYPE => $type,
                AddEventRequest::KEY_LABEL => $label,
                AddEventRequest::KEY_REFERENCE => $reference,
                AddEventRequest::KEY_BODY => $body,
            ],
            $responseData
        );
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
        $token = $this->createToken($jobLabel);

        self::assertSame(0, $this->eventRepository->count([]));

        $firstRequestPayloadHeader = $firstRequestPayload[AddEventRequest::KEY_HEADER_SECTION];
        $firstRequestPayloadHeader[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;
        $firstRequestPayload[AddEventRequest::KEY_HEADER_SECTION] = $firstRequestPayloadHeader;

        $firstResponse = $this->applicationClient->makeAddEventRequest($token, $firstRequestPayload);
        self::assertSame(1, $this->eventRepository->count([]));

        $secondRequestPayloadHeader = $secondRequestPayload[AddEventRequest::KEY_HEADER_SECTION];
        $secondRequestPayloadHeader[AddEventRequest::KEY_SEQUENCE_NUMBER] = $sequenceNumber;
        $secondRequestPayload[AddEventRequest::KEY_HEADER_SECTION] = $secondRequestPayloadHeader;

        $secondResponse = $this->applicationClient->makeAddEventRequest($token, $secondRequestPayload);
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
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'second request type',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                        AddEventRequest::KEY_LABEL => 'first request label',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'label is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_REFERENCE => 'second request reference',
                        AddEventRequest::KEY_LABEL => 'first request label',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'reference is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'second request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
            ],
            'body is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'first request key' => 'first request value',
                    ],
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_HEADER_SECTION => [
                        AddEventRequest::KEY_TYPE => 'first request type',
                        AddEventRequest::KEY_LABEL => 'first request label',
                        AddEventRequest::KEY_REFERENCE => 'first request reference',
                    ],
                    AddEventRequest::KEY_BODY => [
                        'second request key' => 'second request value',
                    ],
                ],
            ],
        ];
    }

    private function createToken(string $jobLabel): string
    {
        $createTokenResponse = $this->applicationClient->makeCreateTokenRequest(
            $this->authenticationConfiguration->validToken,
            $jobLabel
        );
        $createTokenResponseData = json_decode($createTokenResponse->getBody()->getContents(), true);
        self::assertIsArray($createTokenResponseData);

        return (string) $createTokenResponseData['token'];
    }
}
