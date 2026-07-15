<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\EntityFactory\EventFactory;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractJobStatusTest extends AbstractApplicationTest
{
    /**
     * @param callable(EventFactory, EventRepository, string): void $eventCreator
     * @param callable(Job, string): array<mixed>                   $expectedCreator
     */
    #[DataProvider('statusSuccessDataProvider')]
    public function testStatusSuccess(callable $eventCreator, callable $expectedCreator): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        self::assertSame(0, $jobRepository->count());

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
        );

        $job = $jobRepository->findAll()[0];

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);

        $eventCreator($eventFactory, $eventRepository, $jobLabel);

        $response = $this->applicationClient->makeJobRetrievalRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);

        self::assertEquals($expectedCreator($job, $this->getSelfUrl()), $responseData);
    }

    /**
     * @return array<mixed>
     */
    public static function statusSuccessDataProvider(): array
    {
        return [
            'awaiting-events' => [
                'eventCreator' => function (): void {},
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'awaiting-events',
                        'has_events' => false,
                        'meta_state' => [
                            'pending' => true,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [],
                    ];
                },
            ],
            'ended, unknown end state' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/ended',
                        'event_label',
                        'event_reference',
                        null,
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'ended',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => false,
                        ],
                        'end_state' => 'unknown',
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                            'compiling',
                            'compiled',
                            'executing',
                            'executed',
                        ],
                    ];
                },
            ],
            'ended, known end state' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/ended',
                        'event_label',
                        'event_reference',
                        [
                            'end_state' => 'end_state_value',
                        ],
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'ended',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => false,
                        ],
                        'end_state' => 'end_state_value',
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                            'compiling',
                            'compiled',
                            'executing',
                            'executed',
                        ],
                    ];
                },
            ],
            'executed' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/execution/ended',
                        'event_label',
                        'event_reference',
                        null,
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'executed',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                            'compiling',
                            'compiled',
                            'executing',
                        ],
                    ];
                },
            ],
            'executing' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/execution/started',
                        'event_label',
                        'event_reference',
                        null,
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'executing',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                            'compiling',
                            'compiled',
                        ],
                    ];
                },
            ],
            'compiled' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/compilation/ended',
                        'event_label',
                        'event_reference',
                        null,
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'compiled',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                            'compiling',
                        ],
                    ];
                },
            ],
            'compiling' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel,
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        1,
                        'job/compilation/started',
                        'event_label',
                        'event_reference',
                        null,
                        null,
                    );

                    $eventRepository->add($event);
                },
                'expectedCreator' => function (Job $job, string $selfUrl): array {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => $selfUrl . '/event/add/' . $job->getToken(),
                        'state' => 'compiling',
                        'has_events' => true,
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                        'previous_states' => [
                            'awaiting-events',
                            'started',
                        ],
                    ];
                },
            ],
        ];
    }

    abstract protected function getSelfUrl(): string;
}
