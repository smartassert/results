<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\EntityFactory\EventFactory;
use App\Repository\JobRepository;
use Symfony\Component\Uid\Ulid;

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
     * @dataProvider listBadMethodDataProvider
     */
    public function testListBadMethod(string $method): void
    {
        $response = $this->applicationClient->makeListEventRequest((string) new Ulid(), (string) new Ulid(), $method);

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function listBadMethodDataProvider(): array
    {
        return [
            'POST' => [
                'method' => 'POST',
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
     * @dataProvider listSuccessDataProvider
     *
     * @param array<array{
     *     jobLabel: non-empty-string,
     *     sequenceNumber: positive-int,
     *     type: non-empty-string,
     *     label: non-empty-string,
     *     reference: non-empty-string
     * }> $eventDataCollection
     * @param array<mixed> $expectedResponseData
     */
    public function testListSuccess(
        callable $jobsCreator,
        array $eventDataCollection,
        string $jobLabel,
        array $expectedResponseData,
    ): void {
        $jobs = $jobsCreator($this->authenticationConfiguration->authenticatedUserId);

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

        $response = $this->applicationClient->makeListEventRequest(
            $this->authenticationConfiguration->validToken,
            $jobLabel
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
    public function listSuccessDataProvider(): array
    {
        $requestJobLabel = $this->createJobLabel();
        $nonUserJobLabels = [
            $this->createJobLabel(),
            $this->createJobLabel(),
        ];

        return [
            'no jobs' => [
                'jobsCreator' => function () {
                    return [];
                },
                'eventDataCollection' => [],
                'job' => $requestJobLabel,
                'expectedResponseData' => [],
            ],
            'no jobs for user' => [
                'jobsCreator' => function () use ($nonUserJobLabels) {
                    return [
                        new Job($nonUserJobLabels[0], md5((string) rand())),
                        new Job($nonUserJobLabels[1], md5((string) rand())),
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
                'job' => $nonUserJobLabels[0],
                'expectedResponseData' => [],
            ],
            'single job for user, single event for user' => [
                'jobsCreator' => function (string $userId) use ($requestJobLabel) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($requestJobLabel, $userId),
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
                'job' => $requestJobLabel,
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
            'multiple jobs, multiple events some for user, events are ordered by sequence number asc' => [
                'jobsCreator' => function (string $userId) use ($requestJobLabel, $nonUserJobLabels) {
                    if ('' === $userId) {
                        return [];
                    }

                    return [
                        new Job($requestJobLabel, $userId),
                        new Job($nonUserJobLabels[0], md5((string) rand())),
                        new Job($nonUserJobLabels[1], md5((string) rand())),
                    ];
                },
                'eventDataCollection' => [
                    [
                        'jobLabel' => $requestJobLabel,
                        'sequenceNumber' => 1,
                        'type' => 'test/started',
                        'label' => 'test1.yml',
                        'reference' => md5('test1.yml'),
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
                        'type' => 'test/started',
                        'label' => 'test2.yml',
                        'reference' => md5('test2.yml'),
                    ],
                    [
                        'jobLabel' => $nonUserJobLabels[1],
                        'sequenceNumber' => 1,
                        'type' => 'job/started',
                        'label' => 'job_1_test.yml',
                        'reference' => md5('job_1_test.yml'),
                    ],
                ],
                'job' => $requestJobLabel,
                'expectedResponseData' => [
                    [
                        'sequence_number' => 1,
                        'job' => $requestJobLabel,
                        'type' => 'test/started',
                        'label' => 'test1.yml',
                        'reference' => md5('test1.yml'),
                    ],
                    [
                        'sequence_number' => 2,
                        'job' => $requestJobLabel,
                        'type' => 'test/started',
                        'label' => 'test2.yml',
                        'reference' => md5('test2.yml'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return non-empty-string
     */
    private function createJobLabel(): string
    {
        $label = (string) new Ulid();
        \assert('' !== $label);

        return $label;
    }
}
