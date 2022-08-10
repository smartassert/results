<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\JobRepository;
use App\Tests\Services\AuthenticationConfiguration;
use Symfony\Component\Uid\Ulid;

abstract class AbstractCreateTokenTest extends AbstractApplicationTest
{
    /**
     * @dataProvider createBadMethodDataProvider
     */
    public function testCreateBadMethod(string $method): void
    {
        $label = (string) new Ulid();

        $response = $this->applicationClient->makeCreateTokenRequest(
            $this->authenticationConfiguration->validToken,
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
            'GET' => [
                'method' => 'GET',
            ],
            'HEAD' => [
                'method' => 'HEAD',
            ],
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
    public function testCreateUnauthorizedUser(callable $tokenCreator): void
    {
        $response = $this->applicationClient->makeCreateTokenRequest(
            $tokenCreator($this->authenticationConfiguration),
            (string) new Ulid()
        );

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function unauthorizedUserDataProvider(): array
    {
        return [
            'no token' => [
                'tokenCreator' => function () {
                    return null;
                },
            ],
            'empty token' => [
                'tokenCreator' => function () {
                    return '';
                },
            ],
            'non-empty invalid token' => [
                'tokenCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return $authenticationConfiguration->invalidToken;
                },
            ],
        ];
    }

    public function testCreateSuccess(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $response = $this->applicationClient->makeCreateTokenRequest(
            $this->authenticationConfiguration->validToken,
            $jobLabel
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $jobRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('token', $responseData);

        $token = $jobRepository->findOneBy(['token' => $responseData['token']]);
        self::assertInstanceOf(Job::class, $token);
        self::assertSame($jobLabel, $token->jobLabel);
    }

    public function testCreateIsIdempotent(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeCreateTokenRequest($this->authenticationConfiguration->validToken, $jobLabel);
        self::assertSame(1, $jobRepository->count([]));

        $this->applicationClient->makeCreateTokenRequest($this->authenticationConfiguration->validToken, $jobLabel);
        self::assertSame(1, $jobRepository->count([]));
    }
}
