<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Job;
use App\Entity\JobInterface;
use App\Repository\JobRepository;
use App\Request\AddEvent\Request;
use App\Tests\Application\AbstractApplicationTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\CallbackReceiverLogReader\Parser;
use SmartAssert\SymfonyRemoteEventRequestFactory\Factory;
use Symfony\Component\Process\Process;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Uid\Ulid;
use webignition\WaitFor\WaitFor;

class NotificationDeliveryTest extends AbstractApplicationTest
{
    use GetHttpAdapter;

    private Factory $remoteEventRequestFactory;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $remoteEventRequestFactory = self::getContainer()->get(Factory::class);
        \assert($remoteEventRequestFactory instanceof Factory);
        $this->remoteEventRequestFactory = $remoteEventRequestFactory;

        $jobLabel = (string) new Ulid();
        $response = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'http://callback-receiver:8080',
        );
        self::assertSame(200, $response->getStatusCode());

        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        $job = $jobRepository->findOneBy(['label' => $jobLabel]);
        self::assertNotNull($job);
        $this->job = $job;
    }

    /**
     * @param callable(non-empty-string): array<array<mixed>> $addEventPayloadsCreator
     * @param callable(JobInterface): array<array<mixed>>     $expectedRequestBodiesCreator
     */
    #[DataProvider('deliveredNotificationsDataProvider')]
    public function testDeliveredNotifications(
        callable $addEventPayloadsCreator,
        string $stopState,
        int $expectedDispatchedNotificationsCount,
        callable $expectedRequestBodiesCreator,
    ): void {
        $addEventPayloads = $addEventPayloadsCreator($this->job->getLabel());

        foreach ($addEventPayloads as $addEventPayload) {
            $event = new RemoteEvent(
                name: 'worker.event',
                id: (string) new Ulid(),
                payload: $addEventPayload,
            );

            $response = $this->applicationClient->makeWorkerEventWebhookRequest(
                $this->remoteEventRequestFactory->createHeaders($event, $this->job->getToken()),
                $this->remoteEventRequestFactory->createBody($event, $this->job->getToken()),
            );

            self::assertSame(202, $response->getStatusCode());
        }

        new WaitFor()->waitFor(
            30,
            function () use ($stopState) {
                $jobState = $this->getJobState($this->job->getLabel());

                return $stopState === $jobState;
            },
            $this->job->getLabel() . '" to be in "' . $stopState . '"',
        );

        sleep(1);

        $process = Process::fromShellCommandline('docker logs callback-receiver');
        $process->run();

        $output = $process->getOutput();
        $parser = new Parser();

        $requests = $parser->parse($output, $expectedDispatchedNotificationsCount);
        self::assertCount($expectedDispatchedNotificationsCount, $requests);

        $expectedRequestBodies = $expectedRequestBodiesCreator($this->job);

        foreach ($expectedRequestBodies as $requestIndex => $expectedRequestBody) {
            $request = $requests[$requestIndex];

            self::assertSame('POST', $request->getMethod());
            self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
            self::assertEquals('/results.job.state_changed', (string) $request->getUri());

            $requestData = json_decode($request->getBody()->getContents(), true);
            self::assertIsArray($requestData);

            self::assertEquals($expectedRequestBody, $requestData);
        }
    }

    /**
     * @return array<mixed>
     */
    public static function deliveredNotificationsDataProvider(): array
    {
        return [
            'started, compiling, compiled, executing, executed, ended' => [
                'addEventPayloadsCreator' => function (string $jobLabel) {
                    \assert('' !== $jobLabel);

                    return [
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 1,
                            Request::KEY_TYPE => 'job/started',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                        ],
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 2,
                            Request::KEY_TYPE => 'lifecycle/compilation-started',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                        ],
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 3,
                            Request::KEY_TYPE => 'lifecycle/compilation-completed',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                        ],
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 4,
                            Request::KEY_TYPE => 'lifecycle/execution-started',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                        ],
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 5,
                            Request::KEY_TYPE => 'lifecycle/execution-completed',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                        ],
                        [
                            Request::KEY_JOB => $jobLabel,
                            Request::KEY_SEQUENCE_NUMBER => 6,
                            Request::KEY_TYPE => 'job/ended',
                            Request::KEY_LABEL => md5((string) rand()),
                            Request::KEY_REFERENCE => md5((string) rand()),
                            Request::KEY_BODY => [
                                'end_state' => 'complete',
                            ],
                        ],
                    ];
                },
                'stopState' => 'ended',
                'expectedDispatchedNotificationsCount' => 6,
                'expectedRequestBodiesCreator' => function (JobInterface $job) {
                    return [
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'started',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => false,
                                'succeeded' => false,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                            ],
                        ],
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'compiling',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => false,
                                'succeeded' => false,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                                'started',
                            ],
                        ],
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'compiled',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => false,
                                'succeeded' => false,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                                'started',
                                'compiling',
                            ],
                        ],
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'executing',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => false,
                                'succeeded' => false,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                                'started',
                                'compiling',
                                'compiled',
                            ],
                        ],
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'executed',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => false,
                                'succeeded' => false,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                                'started',
                                'compiling',
                                'compiled',
                                'executing',
                            ],
                        ],
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'empty',
                            'state' => 'ended',
                            'has_events' => true,
                            'meta_state' => [
                                'pending' => false,
                                'ended' => true,
                                'succeeded' => true,
                            ],
                            'previous_states' => [
                                'awaiting-events',
                                'started',
                                'compiling',
                                'compiled',
                                'executing',
                                'executed',
                            ],
                            'end_state' => 'complete',
                        ],
                    ];
                },
            ],
        ];
    }

    private function getJobState(string $jobLabel): ?string
    {
        $response = $this->applicationClient->makeJobRetrievalRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        \assert(is_array($responseData));

        $state = $responseData['state'] ?? null;

        return is_string($state) ? $state : null;
    }
}
