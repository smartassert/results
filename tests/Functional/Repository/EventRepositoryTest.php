<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Event;
use App\Entity\Job;
use App\Entity\Reference;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use App\Repository\ReferenceRepository;
use App\Tests\Services\EventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class EventRepositoryTest extends WebTestCase
{
    private const USER_ID = 'user id';
    private const JOB1_LABEL = 'job 1 label';
    private const JOB2_LABEL = 'job 2 label';

    private EventRepository $eventRepository;
    private JobRepository $jobRepository;
    private EventFactory $eventFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(EventRepository::class);
        \assert($repository instanceof EventRepository);
        $this->eventRepository = $repository;

        $eventFactory = self::getContainer()->get(EventFactory::class);
        \assert($eventFactory instanceof EventFactory);
        $this->eventFactory = $eventFactory;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        foreach ($repository->findAll() as $entity) {
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

        $jobRepository->add(new Job('job 1 token', self::JOB1_LABEL, self::USER_ID));
        $jobRepository->add(new Job('job 2 token', self::JOB2_LABEL, self::USER_ID));
    }

    /**
     * @dataProvider findByTypeScopeDataProvider
     *
     * @param Event[]          $events
     * @param non-empty-string $type
     * @param string[]         $expectedEventIds
     */
    public function testFindByType(array $events, string $jobLabel, string $type, array $expectedEventIds): void
    {
        foreach ($events as $event) {
            $this->eventFactory->persist($event);
        }

        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);
        \assert($job instanceof Job);

        $foundEvents = $this->eventRepository->findByType($job, $type);
        $foundEventIds = [];

        foreach ($foundEvents as $foundEvent) {
            $foundEventIds[] = ObjectReflector::getProperty($foundEvent, 'id');
        }

        self::assertSame($expectedEventIds, $foundEventIds);
    }

    /**
     * @return array<mixed>
     */
    public function findByTypeScopeDataProvider(): array
    {
        return [
            'no events, wildcard type' => [
                'events' => [],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/*',
                'expectedEventIds' => [],
            ],
            'no matching events, wildcard type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        2,
                        self::JOB1_LABEL,
                        'test/passed',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/*',
                'expectedEventIds' => [],
            ],
            'single matching event, wildcard type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        2,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 2')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/*',
                'expectedEventIds' => ['eventId1'],
            ],
            'multiple matching events, wildcard type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        1,
                        self::JOB2_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId3',
                        2,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 2')
                    ),
                    new Event(
                        'eventId4',
                        1,
                        self::JOB1_LABEL,
                        'job/ended',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/*',
                'expectedEventIds' => ['eventId1', 'eventId4'],
            ],
            'no events, full type' => [
                'events' => [],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/started',
                'expectedEventIds' => [],
            ],
            'no matching events, full type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        2,
                        self::JOB1_LABEL,
                        'test/passed',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/started',
                'expectedEventIds' => [],
            ],
            'single matching event, full type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        2,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 2')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/started',
                'expectedEventIds' => ['eventId1'],
            ],
            'multiple matching events, full type' => [
                'events' => [
                    new Event(
                        'eventId1',
                        1,
                        self::JOB1_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId2',
                        1,
                        self::JOB2_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId3',
                        2,
                        self::JOB1_LABEL,
                        'test/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 2')
                    ),
                    new Event(
                        'eventId4',
                        1,
                        self::JOB1_LABEL,
                        'job/ended',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                    new Event(
                        'eventId5',
                        5,
                        self::JOB1_LABEL,
                        'job/started',
                        [],
                        new Reference(self::JOB1_LABEL, 'reference 1')
                    ),
                ],
                'jobLabel' => self::JOB1_LABEL,
                'type' => 'job/started',
                'expectedEventIds' => ['eventId1', 'eventId5'],
            ],
        ];
    }
}
