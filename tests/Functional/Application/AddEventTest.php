<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Controller\EventController;
use App\Entity\JobInterface;
use App\EntityFactory\EventFactory;
use App\Enum\JobState as JobStateEnum;
use App\Event\JobStateChangedEvent;
use App\Model\JobState;
use App\ObjectFactory\JobStateFactory;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use App\Request\AddEventRequest;
use App\Tests\Application\AbstractAddEventTest;
use App\Tests\Services\EventRecorder;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Ulid;

class AddEventTest extends AbstractAddEventTest
{
    use GetClientAdapterTrait;

    /**
     * @param callable(EventFactory, EventRepository, string): void $eventCreator
     * @param callable(JobInterface): ?JobStateChangedEvent         $expectedEventCreator
     */
    #[DataProvider('dispatchJobStateChangedEventDataProvider')]
    public function testDispatchJobStateChangedEvent(
        callable $eventCreator,
        AddEventRequest $addEventRequest,
        callable $expectedEventCreator,
    ): void {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        self::assertSame(0, $jobRepository->count());

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );

        $job = $jobRepository->findAll()[0];

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);

        $eventCreator($eventFactory, $eventRepository, $jobLabel);

        $eventController = self::getContainer()->get(EventController::class);
        \assert($eventController instanceof EventController);

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);

        $jobStateFactory = self::getContainer()->get(JobStateFactory::class);
        \assert($jobStateFactory instanceof JobStateFactory);

        $eventDispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);

        $eventController->add(
            $eventFactory,
            $jobStateFactory,
            $eventDispatcher,
            $addEventRequest,
            $job
        );

        $eventRecorder = self::getContainer()->get(EventRecorder::class);
        \assert($eventRecorder instanceof EventRecorder);

        $events = $eventRecorder->all(JobStateChangedEvent::class);
        $recordedEvent = $events[0] ?? null;

        $expectedEvent = $expectedEventCreator($job);
        self::assertEquals($expectedEvent, $recordedEvent);
    }

    /**
     * @return array<mixed>
     */
    public static function dispatchJobStateChangedEventDataProvider(): array
    {
        return [
            'no events -> ended' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel
                ): void {},
                'addEventRequest' => new AddEventRequest(
                    1,
                    'job/ended',
                    'event label',
                    md5('event label'),
                    null,
                    [
                        'end_state' => 'end state value',
                    ],
                ),
                'expectedEventCreator' => function (JobInterface $job): JobStateChangedEvent {
                    $jobState = new JobState(JobStateEnum::ENDED);
                    $jobState->setEndState('end state value');

                    return new JobStateChangedEvent($job, $jobState);
                },
            ],
            'awaiting-events -> job-started' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel
                ): void {},
                'addEventRequest' => new AddEventRequest(
                    1,
                    'job/started',
                    'event label',
                    md5('event label'),
                    null,
                    null,
                ),
                'expectedEventCreator' => function (JobInterface $job): JobStateChangedEvent {
                    return new JobStateChangedEvent($job, new JobState(JobStateEnum::STARTED));
                },
            ],
            'ended -> ended' => [
                'eventCreator' => function (
                    EventFactory $eventFactory,
                    EventRepository $eventRepository,
                    string $jobLabel
                ): void {
                    \assert('' !== $jobLabel);

                    $event = $eventFactory->create(
                        $jobLabel,
                        2,
                        'job/ended',
                        $jobLabel,
                        md5($jobLabel),
                        [
                            'end_state' => 'end state value',
                        ],
                        null,
                    );

                    $eventRepository->add($event);
                },
                'addEventRequest' => new AddEventRequest(
                    1,
                    'job/ended',
                    'event label',
                    md5('event label'),
                    null,
                    [
                        'end_state' => 'end state value',
                    ],
                ),
                'expectedEventCreator' => function (): null {
                    return null;
                },
            ],
        ];
    }
}
