<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\EntityFactory\EventFactory;
use App\ObjectFactory\UlidFactory;
use App\Repository\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractListEventTest extends AbstractApplicationTest
{
    private JobRepository $jobRepository;
    private EventFactory $eventFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        $this->jobRepository = $jobRepository;

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);
        $this->eventFactory = $eventFactory;
    }

    /**
     * @param array<array{
     *     jobLabel: non-empty-string,
     *     sequenceNumber: positive-int,
     *     type: non-empty-string,
     *     label: non-empty-string,
     *     reference: non-empty-string
     * }> $eventDataCollection
     * @param array<mixed> $expectedResponseData
     */
    #[DataProvider('listSuccessDataProvider')]
    public function testListSuccess(
        callable $jobsCreator,
        array $eventDataCollection,
        string $jobLabel,
        ?string $eventReference,
        ?string $eventType,
        array $expectedResponseData,
    ): void {
        $jobs = $jobsCreator(self::$users->get('user@example.com')['id']);

        foreach ($jobs as $job) {
            $this->jobRepository->add($job);
        }

        foreach ($eventDataCollection as $eventData) {
            $this->eventFactory->create(
                $eventData['jobLabel'],
                $eventData['sequenceNumber'],
                $eventData['type'],
                $eventData['label'],
                $eventData['reference'],
                null,
                null
            );
        }

        $response = $this->applicationClient->makeEventListRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            $eventReference,
            $eventType,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public static function listSuccessDataProvider(): array
    {
        $requestJobLabel = (new UlidFactory())->create();
        $nonUserJobLabels = [
            (new UlidFactory())->create(),
            (new UlidFactory())->create(),
        ];

        $ulidFactory = new UlidFactory();

        return [
            'no jobs' => [
                'jobsCreator' => function () {
                    return [];
                },
                'eventDataCollection' => [],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5((string) rand()),
                'eventType' => null,
                'expectedResponseData' => [],
            ],
            'no jobs for user' => [
                'jobsCreator' => function () use ($ulidFactory, $nonUserJobLabels) {
                    return [
                        new Job($ulidFactory->create(), $nonUserJobLabels[0], md5((string) rand())),
                        new Job($ulidFactory->create(), $nonUserJobLabels[1], md5((string) rand())),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $nonUserJobLabels[0],
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_0_test.yml',
                        'reference' => md5('job_0_test.yml'),
                    ],
                    [
                        'jobLabel' => $nonUserJobLabels[1],
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_1_test.yml',
                        'reference' => md5('job_1_test.yml'),
                    ],
                ],
                'jobLabel' => $nonUserJobLabels[0],
                'eventReference' => md5('job_0_test.yml'),
                'eventType' => null,
                'expectedResponseData' => [],
            ],
            'no reference' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 2,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => null,
                'eventType' => null,
                'expectedResponseData' => [
                    [
                        'sequence_number' => 1,
                        'job' => $requestJobLabel,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'sequence_number' => 2,
                        'job' => $requestJobLabel,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
            ],
            'no events for reference' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 2,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5((string) rand()),
                'eventType' => null,
                'expectedResponseData' => [],
            ],
            'single job for user, single event for user' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5('test.yml'),
                'eventType' => null,
                'expectedResponseData' => [
                    [
                        'sequence_number' => 1,
                        'job' => $requestJobLabel,
                        'type' => 'job/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
            ],
            'multiple jobs, multiple events for user, events are ordered by sequence number asc' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel, $nonUserJobLabels) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                        new Job($ulidFactory->create(), $nonUserJobLabels[0], md5((string) rand())),
                        new Job($ulidFactory->create(), $nonUserJobLabels[1], md5((string) rand())),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $nonUserJobLabels[0],
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_0_test.yml',
                        'reference' => md5('job_0_test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 2,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $nonUserJobLabels[1],
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_1_test.yml',
                        'reference' => md5('job_1_test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5('test.yml'),
                'eventType' => null,
                'expectedResponseData' => [
                    [
                        'sequence_number' => 1,
                        'job' => $requestJobLabel,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'sequence_number' => 2,
                        'job' => $requestJobLabel,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
            ],
            'no events for type' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5('test.yml'),
                'eventType' => 'job/ended',
                'expectedResponseData' => [],
            ],
            'filter events by type' => [
                'jobsCreator' => function (string $userId) use ($ulidFactory, $requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($ulidFactory->create(), $requestJobLabel, $userId),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_0_test.yml',
                        'reference' => md5('job_0_test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 2,
                        'type' => 'test/passed',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_1_test.yml',
                        'reference' => md5('job_1_test.yml'),
                    ],
                ],
                'jobLabel' => $requestJobLabel,
                'eventReference' => md5('test.yml'),
                'eventType' => 'test/started',
                'expectedResponseData' => [
                    [
                        'sequence_number' => 1,
                        'job' => $requestJobLabel,
                        'type' => 'test/started',
                        'label' => 'test.yml',
                        'reference' => md5('test.yml'),
                    ],
                ],
            ],
        ];
    }
}
