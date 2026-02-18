<?php

declare(strict_types=1);

namespace App\Tests\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractBadRequestMethodTest extends AbstractApplicationTest
{
    #[DataProvider('createJobBadMethodDataProvider')]
    public function testCreateJobBadMethod(string $method): void
    {
        $label = (string) new Ulid();

        $response = $this->applicationClient->makeJobRequest(
            self::$apiTokens->get('user@example.com'),
            $label,
            $method
        );

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public static function createJobBadMethodDataProvider(): array
    {
        return [
            'PUT' => [
                'method' => 'PUT',
            ],
            'DELETE' => [
                'method' => 'DELETE',
            ],
        ];
    }

    #[DataProvider('addEventBadMethodDataProvider')]
    public function testAddEventBadMethod(string $method): void
    {
        $response = $this->applicationClient->makeEventAddRequest((string) new Ulid(), [], $method);

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public static function addEventBadMethodDataProvider(): array
    {
        return [
            'GET' => [
                'method' => 'GET',
            ],
            'PUT' => [
                'method' => 'PUT',
            ],
            'DELETE' => [
                'method' => 'DELETE',
            ],
        ];
    }

    #[DataProvider('listEventsBadMethodDataProvider')]
    public function testListEventsBadMethod(string $method): void
    {
        $response = $this->applicationClient->makeEventListRequest(
            (string) new Ulid(),
            (string) new Ulid(),
            md5((string) rand()),
            null,
            $method
        );

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public static function listEventsBadMethodDataProvider(): array
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
