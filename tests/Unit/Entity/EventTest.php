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
                    md5('empty payload label'),
                    'job/started',
                    md5('empty payload reference'),
                    []
                ),
                'expected' => [
                    'label' => md5('empty payload label'),
                    'type' => 'job/started',
                    'reference' => md5('empty payload reference'),
                    'payload' => [],
                ],
            ],
            'non-empty payload' => [
                'event' => new Event(
                    md5('label'),
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
            ],
        ];
    }
}
