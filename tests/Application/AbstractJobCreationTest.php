<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\JobRepository;
use App\Tests\Services\AuthenticationConfiguration;
use Symfony\Component\Uid\Ulid;

abstract class AbstractJobCreationTest extends AbstractApplicationTest
{
    /**
     * @dataProvider unauthorizedUserDataProvider
     */
    public function testCreateUnauthorizedUser(callable $userTokenCreator): void
    {
        $response = $this->applicationClient->makeJobCreateRequest(
            $userTokenCreator(self::$authenticationConfiguration),
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
            'no user token' => [
                'userTokenCreator' => function () {
                    return null;
                },
            ],
            'empty user token' => [
                'userTokenCreator' => function () {
                    return '';
                },
            ],
            'non-empty invalid user token' => [
                'userTokenCreator' => function (AuthenticationConfiguration $authenticationConfiguration) {
                    return $authenticationConfiguration->getInvalidApiToken();
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

        $response = $this->applicationClient->makeJobCreateRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $jobLabel
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $jobRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('label', $responseData);
        self::assertSame($jobLabel, $responseData['label']);
        self::assertArrayHasKey('token', $responseData);

        $job = $jobRepository->findOneBy(['token' => $responseData['token']]);
        self::assertInstanceOf(Job::class, $job);
        self::assertSame($jobLabel, $job->label);
    }

    public function testCreateIsIdempotent(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobCreateRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $jobLabel
        );
        self::assertSame(1, $jobRepository->count([]));

        $this->applicationClient->makeJobCreateRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $jobLabel
        );
        self::assertSame(1, $jobRepository->count([]));
    }
}
