<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Event;
use App\Entity\Reference;
use App\ObjectFactory\UlidFactory;
use App\Repository\EventRepository;
use App\Repository\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class EventTest extends WebTestCase
{
    private EventRepository $eventRepository;
    private ReferenceRepository $referenceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(EventRepository::class);
        \assert($repository instanceof EventRepository);
        $this->eventRepository = $repository;

        $referenceRepository = self::getContainer()->get(ReferenceRepository::class);
        \assert($referenceRepository instanceof ReferenceRepository);
        $this->referenceRepository = $referenceRepository;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        foreach ($repository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        foreach ($referenceRepository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param non-empty-string $id
     * @param array<mixed>     $body
     */
    public function testCreate(
        string $id,
        int $sequenceNumber,
        string $job,
        string $type,
        ?array $body,
        Reference $referenceEntity,
    ): void {
        $this->referenceRepository->add($referenceEntity);

        self::assertSame(0, $this->eventRepository->count([]));

        $event = new Event($id, $sequenceNumber, $job, $type, $body, $referenceEntity);

        $this->eventRepository->add($event);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $entityManager->clear();

        $events = $this->eventRepository->findAll();
        $retrievedEvent = $events[0];

        self::assertSame(1, $this->eventRepository->count([]));

        self::assertSame($id, ObjectReflector::getProperty($retrievedEvent, 'id'));
        self::assertSame($sequenceNumber, ObjectReflector::getProperty($retrievedEvent, 'sequenceNumber'));
        self::assertSame($job, ObjectReflector::getProperty($retrievedEvent, 'job'));
        self::assertSame($type, ObjectReflector::getProperty($retrievedEvent, 'type'));
        self::assertSame($body, ObjectReflector::getProperty($retrievedEvent, 'body'));
    }

    /**
     * @return array<mixed>
     */
    public static function createDataProvider(): array
    {
        $ulidFactory = new UlidFactory();

        return [
            'null body' => [
                'id' => $ulidFactory->create(),
                'sequenceNumber' => 1,
                'job' => md5('null body job'),
                'type' => 'job/started',
                'body' => null,
                'referenceEntity' => new Reference('null body label', 'null body reference'),
            ],
            'empty body' => [
                'id' => $ulidFactory->create(),
                'sequenceNumber' => 2,
                'job' => md5('empty body job'),
                'type' => 'job/started',
                'body' => [],
                'referenceEntity' => new Reference('empty body label', 'empty body reference'),
            ],
            'non-empty body' => [
                'id' => $ulidFactory->create(),
                'sequenceNumber' => 3,
                'job' => md5('job'),
                'type' => 'job/finished',
                'body' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => [
                        'key31' => 'value 31',
                        'key32' => 'value 32',
                    ],
                ],
                'referenceEntity' => new Reference('non-empty body label', 'non-empty body reference'),
            ],
        ];
    }
}
