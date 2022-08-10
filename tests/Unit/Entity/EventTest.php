<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
use App\Entity\Reference;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param array<mixed> $expected
     */
    public function testJsonSerialize(Event $event, array $expected): void
    {
        self::assertSame($expected, $event->jsonSerialize());
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty payload' => [
                'event' => new Event(
                    1,
                    md5('empty payload job'),
                    'job/started',
                    [],
                    new Reference('empty payload label', md5('empty payload reference'))
                ),
                'expected' => [
                    'sequence_number' => 1,
                    'job' => md5('empty payload job'),
                    'type' => 'job/started',
                    'label' => 'empty payload label',
                    'reference' => md5('empty payload reference'),
                    'body' => [],
                ],
            ],
            'non-empty payload' => [
                'event' => new Event(
                    2,
                    md5('job'),
                    'job/finished',
                    [
                        'key1' => 'value1',
                        'key2' => 'value2',
                        'key3' => [
                            'key31' => 'value 31',
                            'key32' => 'value 32',
                        ],
                    ],
                    new Reference('non-empty payload label', md5('reference'))
                ),
                'expected' => [
                    'sequence_number' => 2,
                    'job' => md5('job'),
                    'type' => 'job/finished',
                    'label' => 'non-empty payload label',
                    'reference' => md5('reference'),
                    'body' => [
                        'key1' => 'value1',
                        'key2' => 'value2',
                        'key3' => [
                            'key31' => 'value 31',
                            'key32' => 'value 32',
                        ],
                    ],
                ],
            ],
        ];
    }
}
