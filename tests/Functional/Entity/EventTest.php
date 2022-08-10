<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Event;
use App\Entity\Reference;
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
     * @param array<mixed> $body
     */
    public function testCreate(
        int $sequenceNumber,
        string $job,
        string $type,
        array $body,
        Reference $referenceEntity,
    ): void {
        $this->referenceRepository->add($referenceEntity);

        self::assertSame(0, $this->eventRepository->count([]));

        $event = new Event($sequenceNumber, $job, $type, $body, $referenceEntity);

        $this->eventRepository->add($event);

        self::assertSame(1, $this->eventRepository->count([]));

        self::assertSame($sequenceNumber, ObjectReflector::getProperty($event, 'sequenceNumber'));
        self::assertSame($job, ObjectReflector::getProperty($event, 'job'));
        self::assertSame($type, ObjectReflector::getProperty($event, 'type'));
        self::assertSame($body, ObjectReflector::getProperty($event, 'body'));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        return [
            'empty payload' => [
                'sequence_number' => 1,
                'job' => md5('empty payload job'),
                'type' => 'job/started',
                'body' => [],
                'referenceEntity' => new Reference('empty payload label', 'empty payload reference'),
            ],
            'non-empty payload' => [
                'sequence_number' => 2,
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
                'referenceEntity' => new Reference('non-empty payload label', 'non-empty payload reference'),
            ],
        ];
    }
}
