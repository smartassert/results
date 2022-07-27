<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Event;
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
                    md5('empty payload reference'),
                    []
                ),
                'expected' => [
                    'sequence_number' => 1,
                    'job' => md5('empty payload job'),
                    'type' => 'job/started',
                    'reference' => md5('empty payload reference'),
                    'payload' => [],
                ],
            ],
            'non-empty payload' => [
                'event' => new Event(
                    2,
                    md5('job'),
                    'job/finished',
                    md5('reference'),
                    [
                        'key1' => 'value1',
                        'key2' => 'value2',
                        'key3' => [
                            'key31' => 'value 31',
                            'key32' => 'value 32',
                        ],
                    ]
                ),
                'expected' => [
                    'sequence_number' => 2,
                    'job' => md5('job'),
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
            ],
        ];
    }
}
