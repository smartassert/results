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
        return [
            'sequence number missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([]),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_SEQUENCE_NUMBER => [
                                'value' => null,
                                'message' => 'Required field "sequence_number" invalid, ' .
                                    'missing from request or not an integer.',
                            ],
                        ],
                    ],
                ],
            ],
            'type missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([]),
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
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([]),
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
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_PAYLOAD => json_encode([]),
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
            'payload missing' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_PAYLOAD => [
                                'value' => null,
                                'message' => 'Required field "payload" invalid, ' .
                                    'missing from request or not a JSON string that decodes to an array.',
                            ],
                        ],
                    ],
                ],
            ],
            'payload is not json' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                    AddEventRequest::KEY_PAYLOAD => 'foo',
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_PAYLOAD => [
                                'value' => null,
                                'message' => 'Required field "payload" invalid, ' .
                                    'missing from request or not a JSON string that decodes to an array.',
                            ],
                        ],
                    ],
                ],
            ],
            'payload does not decode to an array' => [
                'requestPayload' => [
                    AddEventRequest::KEY_SEQUENCE_NUMBER => 123,
                    AddEventRequest::KEY_TYPE => 'type',
                    AddEventRequest::KEY_LABEL => 'label',
                    AddEventRequest::KEY_REFERENCE => 'reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode('foo'),
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            AddEventRequest::KEY_PAYLOAD => [
                                'value' => null,
                                'message' => 'Required field "payload" invalid, ' .
                                    'missing from request or not a JSON string that decodes to an array.',
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

        $sequenceNumber = rand();
        $type = md5((string) rand());
        $label = md5((string) rand());
        $reference = md5((string) rand());
        $payloadData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key3.1' => 'value3.1',
                'key3.2' => 'value3.2',
            ],
        ];

        $requestPayload = [
            AddEventRequest::KEY_SEQUENCE_NUMBER => (string) $sequenceNumber,
            AddEventRequest::KEY_TYPE => $type,
            AddEventRequest::KEY_LABEL => $label,
            AddEventRequest::KEY_REFERENCE => $reference,
            AddEventRequest::KEY_PAYLOAD => (string) json_encode($payloadData),
        ];

        $response = $this->applicationClient->makeAddEventRequest($token, $requestPayload);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $this->eventRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertSame(
            [
                'sequence_number' => $sequenceNumber,
                'job' => $jobLabel,
                AddEventRequest::KEY_TYPE => $type,
                AddEventRequest::KEY_LABEL => $label,
                AddEventRequest::KEY_REFERENCE => $reference,
                AddEventRequest::KEY_PAYLOAD => $payloadData,
            ],
            $responseData
        );
    }

    /**
     * @dataProvider addIsIdempotentDataProvider
     *
     * @param array{type: string, reference: string, payload: array<mixed>} $firstRequestPayload
     * @param array{type: string, reference: string, payload: array<mixed>} $secondRequestPayload
     */
    public function testAddIsIdempotent(
        string $jobLabel,
        int $sequenceNumber,
        array $firstRequestPayload,
        array $secondRequestPayload
    ): void {
        $token = $this->createToken($jobLabel);

        self::assertSame(0, $this->eventRepository->count([]));

        $firstResponse = $this->applicationClient->makeAddEventRequest(
            $token,
            array_merge([AddEventRequest::KEY_SEQUENCE_NUMBER => (string) $sequenceNumber], $firstRequestPayload)
        );
        self::assertSame(1, $this->eventRepository->count([]));

        $secondResponse = $this->applicationClient->makeAddEventRequest(
            $token,
            array_merge([AddEventRequest::KEY_SEQUENCE_NUMBER => (string) $sequenceNumber], $secondRequestPayload)
        );
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
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'second request type',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
            ],
            'label is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_REFERENCE => 'second request reference',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
            ],
            'reference is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'second request reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
            ],
            'payload is not modified by second request' => [
                'jobLabel' => (string) new Ulid(),
                'sequence_number' => rand(),
                'firstRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'first request key' => 'first request value',
                    ]),
                ],
                'secondRequestPayload' => [
                    AddEventRequest::KEY_TYPE => 'first request type',
                    AddEventRequest::KEY_LABEL => 'first request label',
                    AddEventRequest::KEY_REFERENCE => 'first request reference',
                    AddEventRequest::KEY_PAYLOAD => json_encode([
                        'second request key' => 'second request value',
                    ]),
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
