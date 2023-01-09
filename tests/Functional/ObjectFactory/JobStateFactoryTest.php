<?php

declare(strict_types=1);

namespace App\Tests\Functional\ObjectFactory;

use App\Entity\Event;
use App\Entity\Job;
use App\Entity\Reference;
use App\Enum\JobState as JobStateEnum;
use App\Model\JobState;
use App\ObjectFactory\JobStateFactory;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use App\Repository\ReferenceRepository;
use App\Tests\Services\EventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobStateFactoryTest extends WebTestCase
{
    private const JOB_LABEL = 'job label';
    private JobRepository $jobRepository;
    private EventFactory $eventFactory;
    private JobStateFactory $jobStateFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStateFactory = self::getContainer()->get(JobStateFactory::class);
        \assert($jobStateFactory instanceof JobStateFactory);
        $this->jobStateFactory = $jobStateFactory;

        $eventRepository = self::getContainer()->get(EventRepository::class);
        \assert($eventRepository instanceof EventRepository);

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);
        $this->eventFactory = $eventFactory;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        foreach ($eventRepository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        $referenceRepository = self::getContainer()->get(ReferenceRepository::class);
        \assert($referenceRepository instanceof ReferenceRepository);
        foreach ($referenceRepository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);
        $this->jobRepository = $jobRepository;
        foreach ($jobRepository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        $jobRepository->add(new Job('job token', self::JOB_LABEL, 'user id'));
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param Event[] $events
     */
    public function testCreate(array $events, string $jobLabel, JobState $expected): void
    {
        foreach ($events as $event) {
            $this->eventFactory->persist($event);
        }

        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);
        \assert($job instanceof Job);

        self::assertEquals($expected, $this->jobStateFactory->create($job));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        return [
            'no events' => [
                'events' => [],
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::UNKNOWN),
            ],
            'job/ended only, no end state' => [
                'events' => $this->createFoo(['job/ended']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'unknown'),
            ],
            'job/ended only, has end state' => [
                'events' => $this->createFoo(['job/ended:complete']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'complete'),
            ],
            'multiple job/ended, has end state' => [
                'events' => $this->createFoo(['job/ended:timed-out', 'job/ended:failed/compilation']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'timed-out'),
            ],
            'job/execution/ended only' => [
                'events' => $this->createFoo(['job/execution/ended']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::EXECUTED),
            ],
            'job/execution/started only' => [
                'events' => $this->createFoo(['job/execution/started']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::EXECUTING),
            ],
            'job/compilation/ended only' => [
                'events' => $this->createFoo(['job/compilation/ended']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::COMPILED),
            ],
            'job/compilation/started only' => [
                'events' => $this->createFoo(['job/compilation/started']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::COMPILING),
            ],
            'job/started only' => [
                'events' => $this->createFoo(['job/started']),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::STARTED),
            ],
            'full successful event set' => [
                'events' => $this->createFoo([
                    'job/started',
                    'job/compilation/started',
                    'job/compilation/ended',
                    'job/execution/started',
                    'job/execution/ended',
                    'job/ended:complete',
                ]),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'complete'),
            ],
            'full compilation failure event set' => [
                'events' => $this->createFoo([
                    'job/started',
                    'job/compilation/started',
                    'job/compilation/failed',
                    'job/ended:failed/compilation',
                ]),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'failed/compilation'),
            ],
            'full execution failure event set' => [
                'events' => $this->createFoo([
                    'job/started',
                    'job/compilation/started',
                    'job/compilation/ended',
                    'job/execution/started',
                    'job/ended:failed/execution',
                ]),
                'jobLabel' => self::JOB_LABEL,
                'expected' => new JobState(JobStateEnum::ENDED, 'failed/execution'),
            ],
        ];
    }

    /**
     * @param string[] $types
     *
     * @return Event[]
     */
    private function createFoo(array $types): array
    {
        $events = [];
        $sequenceNumber = 1;

        foreach ($types as $type) {
            $body = [];

            if (str_starts_with($type, 'job/ended') && 'job/ended' !== $type) {
                $endState = str_replace('job/ended:', '', $type);
                $type = 'job/ended';
                $body = [
                    'end_state' => $endState,
                ];
            }

            $events[] = new Event(
                'eventId' . $sequenceNumber,
                $sequenceNumber,
                self::JOB_LABEL,
                $type,
                $body,
                new Reference(self::JOB_LABEL, 'reference')
            );

            ++$sequenceNumber;
        }

        return $events;
    }
}
