<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class EventTest extends WebTestCase
{
    private EventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(EventRepository::class);
        \assert($repository instanceof EventRepository);
        $this->repository = $repository;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);

        foreach ($repository->findAll() as $entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<mixed> $payload
     */
    public function testCreate(
        int $sequenceNumber,
        string $job,
        string $type,
        string $label,
        string $reference,
        array $payload
    ): void {
        self::assertSame(0, $this->repository->count([]));

        $event = new Event($sequenceNumber, $job, $type, $label, $reference, $payload);

        $this->repository->add($event);

        self::assertSame(1, $this->repository->count([]));

        self::assertSame($sequenceNumber, ObjectReflector::getProperty($event, 'sequenceNumber'));
        self::assertSame($job, ObjectReflector::getProperty($event, 'job'));
        self::assertSame($type, ObjectReflector::getProperty($event, 'type'));
        self::assertSame($label, ObjectReflector::getProperty($event, 'label'));
        self::assertSame($reference, ObjectReflector::getProperty($event, 'reference'));
        self::assertSame($payload, ObjectReflector::getProperty($event, 'payload'));
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
                'label' => 'empty payload label',
                'reference' => md5('empty payload reference'),
                'payload' => [],
            ],
            'non-empty payload' => [
                'sequence_number' => 2,
                'job' => md5('job'),
                'type' => 'job/finished',
                'label' => 'non-empty payload label',
                'reference' => md5('reference'),
                'payload' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => [
                        'key31' => 'value 31',
                        'key32' => 'value 32',
                    ],
                ],
            ],
        ];
    }
}
