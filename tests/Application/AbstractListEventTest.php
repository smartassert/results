<?php

declare(strict_types=1);

namespace App\Tests\Application;

use Symfony\Component\Uid\Ulid;

abstract class AbstractListEventTest extends AbstractApplicationTest
{
    /**
     * @dataProvider listBadMethodDataProvider
     */
    public function testListBadMethod(string $method): void
    {
        $response = $this->applicationClient->makeListEventRequest((string) new Ulid(), (string) new Ulid(), $method);

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function listBadMethodDataProvider(): array
    {
        return [
            'POST' => [
                'method' => 'POST',
            ],
            'PUT' => [
                'method' => 'PUT',
            ],
            'DELETE' => [
                'method' => 'DELETE',
            ],
        ];
    }
}
