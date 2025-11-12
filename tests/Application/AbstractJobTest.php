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

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testRequestUnauthorizedUser(?string $token, string $method): void
    {
        $response = $this->applicationClient->makeJobRequest($token, (string) new Ulid(), $method);

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public static function unauthorizedUserDataProvider(): array
    {
        return [
            'no user token, GET request' => [
                'token' => null,
                'method' => 'GET',
            ],
            'empty user token, GET request' => [
                'token' => '',
                'method' => 'GET',
            ],
            'non-empty invalid user token, GET request' => [
                'token' => 'invalid api token',
                'method' => 'GET',
            ],
            'no user token, POST request' => [
                'token' => null,
                'method' => 'POST',
            ],
            'empty user token, POST request' => [
                'token' => '',
                'method' => 'POST',
            ],
            'non-empty invalid user token, POST request' => [
                'token' => 'invalid api token',
                'method' => 'POST',
            ],
        ];
    }
}
