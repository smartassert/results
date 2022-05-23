<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Event;
use App\Repository\EventRepository;
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

        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param array<mixed> $payload
     */
    public function testCreate(string $label, string $type, string $reference, array $payload): void
    {
        self::assertSame(0, $this->repository->count([]));

        $event = new Event($label, $type, $reference, $payload);

        $this->repository->add($event);

        self::assertSame(1, $this->repository->count([]));

        self::assertSame($label, ObjectReflector::getProperty($event, 'label'));
        self::assertSame($type, ObjectReflector::getProperty($event, 'type'));
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
                'label' => md5('empty payload label'),
                'type' => 'job/started',
                'reference' => md5('empty payload reference'),
                'payload' => [],
            ],
            'non-empty payload' => [
                'label' => md5('label'),
                'type' => 'job/finished',
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
