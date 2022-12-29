<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Event;
use App\Entity\Job;
use App\Entity\Reference;
use App\EntityFactory\ReferenceFactory;
use App\Repository\EventRepository;
use App\Repository\JobRepository;
use App\Repository\ReferenceRepository;
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
    private ReferenceRepository $referenceRepository;
    private ReferenceFactory $referenceFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(EventRepository::class);
        \assert($repository instanceof EventRepository);
        $this->eventRepository = $repository;

        $referenceFactory = self::getContainer()->get(ReferenceFactory::class);
        \assert($referenceFactory instanceof ReferenceFactory);
        $this->referenceFactory = $referenceFactory;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        foreach ($repository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        $referenceRepository = self::getContainer()->get(ReferenceRepository::class);
        \assert($referenceRepository instanceof ReferenceRepository);
        $this->referenceRepository = $referenceRepository;
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
     * @param non-empty-string $scope
     * @param string[]         $expectedEventIds
     */
    public function testFindByTypeScope(array $events, string $jobLabel, string $scope, array $expectedEventIds): void
    {
        foreach ($events as $event) {
            $this->persistEvent($event);
        }

        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);
        \assert($job instanceof Job);

        $foundEvents = $this->eventRepository->findByTypeScope($job, $scope);
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
            'no events' => [
                'events' => [],
                'jobLabel' => self::JOB1_LABEL,
                'scope' => 'job/',
                'expectedEventIds' => [],
            ],
            'no matching events' => [
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
                'scope' => 'job/',
                'expectedEventIds' => [],
            ],
            'single matching event' => [
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
                'scope' => 'job/',
                'expectedEventIds' => ['eventId1'],
            ],
            'multiple matching events' => [
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
                'scope' => 'job/',
                'expectedEventIds' => ['eventId1', 'eventId4'],
            ],
        ];
    }

    private function persistEvent(Event $event): void
    {
        $eventReference = ObjectReflector::getProperty($event, 'reference');
        \assert($eventReference instanceof Reference);

        $referenceLabel = ObjectReflector::getProperty($eventReference, 'label');
        \assert(is_string($referenceLabel));
        \assert('' !== $referenceLabel);

        $referenceReference = ObjectReflector::getProperty($eventReference, 'reference');
        \assert(is_string($referenceReference));
        \assert('' !== $referenceReference);

        $referenceEntity = $this->createReferenceEntity($referenceLabel, $referenceReference);

        $event = $this->createEventWithReference($event, $referenceEntity);

        $this->eventRepository->add($event);
    }

    /**
     * @param non-empty-string $label
     * @param non-empty-string $reference
     */
    private function createReferenceEntity(string $label, string $reference): Reference
    {
        $entity = $this->referenceRepository->findOneBy([
            'label' => $label,
            'reference' => $reference,
        ]);

        if (null === $entity) {
            $entity = $this->referenceFactory->create($label, $reference);
        }

        return $entity;
    }

    private function createEventWithReference(Event $event, Reference $reference): Event
    {
        $reflectionClass = new \ReflectionClass($event);
        $reflectionEvent = $reflectionClass->newInstanceWithoutConstructor();
        \assert($reflectionEvent instanceof Event);

        // id
        // sequenceNumber
        // job
        // type
        // body
        // reference
        // relatedReferences

        $referenceProperty = $reflectionClass->getProperty('reference');
        $referenceProperty->setValue($reflectionEvent, $reference);
//
//        $sequenceNumberProperty = $reflectionClass->getProperty('sequenceNumber');
//        $sequenceNumberProperty->setValue($reflectionEvent, ObjectReflector::getProperty($event, 'sequenceNumber'));

        $propertyNames = ['id', 'sequenceNumber', 'job', 'type', 'body', 'relatedReferences'];
        foreach ($propertyNames as $propertyName) {
            $property = $reflectionClass->getProperty($propertyName);
            $property->setValue($reflectionEvent, ObjectReflector::getProperty($event, $propertyName));
        }

//
//        $labelProperty = $reflectionClass->getProperty('label');
//        $labelProperty->setValue($reflectionEvent, $job->label);
//
//        $eventDeliveryUrlProperty = $reflectionClass->getProperty('eventDeliveryUrl');
//        $eventDeliveryUrlProperty->setValue($reflectionEvent, $job->eventDeliveryUrl);
//
//        $testPathsProperty = $reflectionClass->getProperty('testPaths');
//        $testPathsProperty->setValue($reflectionEvent, $job->testPaths);
//
//        $endStateProperty = $reflectionClass->getProperty('endState');
//        $endStateProperty->setValue($reflectionEvent, null);

        return $reflectionEvent;
    }
}
