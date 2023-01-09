<?php

declare(strict_types=1);

namespace App\Tests\Application;

use Symfony\Component\Uid\Ulid;

abstract class AbstractJobTest extends AbstractApplicationTest
{
    /**
     * @dataProvider createBadMethodDataProvider
     */
    public function testRequestBadMethod(string $method): void
    {
        $label = (string) new Ulid();

        $response = $this->applicationClient->makeJobCreateRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $label,
            $method
        );

        self::assertSame(405, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function createBadMethodDataProvider(): array
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
