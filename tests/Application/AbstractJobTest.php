<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Tests\Services\AuthenticationConfiguration;
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

    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testRequestUnauthorizedUser(callable $userTokenCreator, string $method): void
    {
        $response = $this->applicationClient->makeJobRequest(
            $userTokenCreator(self::$authenticationConfiguration),
            (string) new Ulid(),
            $method
        );

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function unauthorizedUserDataProvider(): array
    {
        $noTokenUserCreator = function () {
            return null;
        };

        $emptyTokenUserCreator = function () {
            return '';
        };

        $validTokenUserCreator = function (AuthenticationConfiguration $authenticationConfiguration) {
            return $authenticationConfiguration->getInvalidApiToken();
        };

        return [
            'no user token, GET request' => [
                'userTokenCreator' => $noTokenUserCreator,
                'method' => 'GET',
            ],
            'empty user token, GET request' => [
                'userTokenCreator' => $emptyTokenUserCreator,
                'method' => 'GET',
            ],
            'non-empty invalid user token, GET request' => [
                'userTokenCreator' => $validTokenUserCreator,
                'method' => 'GET',
            ],
            'no user token, POST request' => [
                'userTokenCreator' => $noTokenUserCreator,
                'method' => 'POST',
            ],
            'empty user token, POST request' => [
                'userTokenCreator' => $emptyTokenUserCreator,
                'method' => 'POST',
            ],
            'non-empty invalid user token, POST request' => [
                'userTokenCreator' => $validTokenUserCreator,
                'method' => 'POST',
            ],
        ];
    }
}
