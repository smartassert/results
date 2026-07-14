<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Job;
use App\Repository\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Ulid;

abstract class AbstractJobCreationTest extends AbstractApplicationTest
{
    #[DataProvider('createSuccessDataProvider')]
    public function testCreateSuccess(?string $notifyUrl, ?string $expectedNotifyUrl): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $response = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            $notifyUrl,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertSame(1, $jobRepository->count([]));

        $responseData = json_decode($response->getBody()->getContents(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('label', $responseData);
        self::assertSame($jobLabel, $responseData['label']);

        $job = $jobRepository->findOneBy(['label' => $responseData['label']]);
        self::assertInstanceOf(Job::class, $job);

        self::assertSame($expectedNotifyUrl, $job->getNotifyUrl());

        self::assertEquals(
            [
                'label' => $jobLabel,
                'event_add_url' => $this->getSelfUrl() . '/event/add/' . $job->getToken(),
                'state' => 'awaiting-events',
                'has_events' => false,
                'meta_state' => [
                    'pending' => true,
                    'ended' => false,
                    'succeeded' => false,
                ],
                'previous_states' => [],
            ],
            $responseData,
        );
    }

    /**
     * @return array<mixed>
     */
    public static function createSuccessDataProvider(): array
    {
        return [
            'null notify url' => [
                'notifyUrl' => null,
                'expectedNotifyUrl' => null,
            ],
            'empty notify url' => [
                'notifyUrl' => '',
                'expectedNotifyUrl' => null,
            ],
            'non-empty notify url' => [
                'notifyUrl' => 'https://example.com/notify',
                'expectedNotifyUrl' => 'https://example.com/notify',
            ],
        ];
    }

    public function testCreateIsIdempotent(): void
    {
        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        self::assertSame(0, $jobRepository->count([]));

        $jobLabel = (string) new Ulid();

        $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );
        self::assertSame(1, $jobRepository->count([]));

        $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            null,
        );
        self::assertSame(1, $jobRepository->count([]));
    }

    abstract protected function getSelfUrl(): string;
}
