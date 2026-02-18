<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Request\AddEventRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractAddEventBadRequestTest extends AbstractApplicationTest
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
     * @param array<string, array<mixed>|string> $requestPayload
     * @param array<mixed>                       $expectedResponseData
     */
    #[DataProvider('addBadRequestDataProvider')]
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
    public static function addBadRequestDataProvider(): array
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
