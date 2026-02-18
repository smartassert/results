<?php

declare(strict_types=1);

namespace App\Tests\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractBadRequestMethodTest extends AbstractApplicationTest
{
    #[DataProvider('createBadMethodDataProvider')]
    public function testRequestBadMethod(string $method): void
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
    public static function createBadMethodDataProvider(): array
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
}
