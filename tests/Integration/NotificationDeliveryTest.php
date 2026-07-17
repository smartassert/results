<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\JobInterface;
use App\ObjectFactory\UlidFactory;
use App\Repository\JobRepository;
use App\Request\AddEventRequest;
use App\Tests\Application\AbstractApplicationTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartAssert\CallbackReceiverLogReader\Parser;
use Symfony\Component\Process\Process;

use function PHPUnit\Framework\assertEquals;

class NotificationDeliveryTest extends AbstractApplicationTest
{
    use GetResultsClientAdapterTrait;

    /**
     * @param array<array<mixed>>                         $addEventPayloads
     * @param callable(JobInterface): array<array<mixed>> $expectedRequestBodiesCreator
     */
    #[DataProvider('deliveredNotificationsDataProvider')]
    public function testDeliveredNotifications(
        array $addEventPayloads,
        string $stopState,
        int $expectedDispatchedNotificationsCount,
        callable $expectedRequestBodiesCreator
    ): void {
        $jobLabel = new UlidFactory()->create();

        $createJobResponse = $this->applicationClient->makeJobCreationRequest(
            self::$apiTokens->get('user@example.com'),
            $jobLabel,
            'http://callback-receiver:8080',
        );

        self::assertSame(200, $createJobResponse->getStatusCode());

        $jobRepository = self::getContainer()->get(JobRepository::class);
        \assert($jobRepository instanceof JobRepository);

        $job = $jobRepository->findOneBy(['label' => $jobLabel]);
        \assert($job instanceof JobInterface);

        $responseData = json_decode($createJobResponse->getBody()->getContents(), true);
        \assert(is_array($responseData));
        \assert(array_key_exists('event_add_url', $responseData));

        $addEventUrl = (string) $responseData['event_add_url'];

        foreach ($addEventPayloads as $addEventPayload) {
            $response = $this->applicationClient->makeEventAddRequest($addEventUrl, $addEventPayload);
            \assert(200 === $response->getStatusCode());
        }

        $this->waitUntilJobStateIs($jobLabel, $stopState);
        sleep(1);

        $process = Process::fromShellCommandline('docker logs callback-receiver');
        $process->run();

        $output = $process->getOutput();
        $parser = new Parser();

        $requests = $parser->parse($output, $expectedDispatchedNotificationsCount);
        self::assertCount($expectedDispatchedNotificationsCount, $requests);

        $expectedRequestBodies = $expectedRequestBodiesCreator($job);

        foreach ($expectedRequestBodies as $requestIndex => $expectedRequestBody) {
            $request = $requests[$requestIndex];

            self::assertSame('POST', $request->getMethod());
            self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
            self::assertEquals('/results.job.state_changed', (string) $request->getUri());

            $requestData = json_decode($request->getBody()->getContents(), true);
            self::assertIsArray($requestData);

            self:assertEquals($expectedRequestBody, $requestData);
        }
    }

    /**
     * @return array<mixed>
     */
    public static function deliveredNotificationsDataProvider(): array
    {
        return [
            'started, compiling, compiled, executing, executed, ended' => [
                'addEventPayloads' => [
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 1,
                        AddEventRequest::KEY_TYPE => 'job/started',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                    ],
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 2,
                        AddEventRequest::KEY_TYPE => 'lifecycle/compilation-started',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                    ],
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 3,
                        AddEventRequest::KEY_TYPE => 'lifecycle/compilation-completed',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                    ],
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 4,
                        AddEventRequest::KEY_TYPE => 'lifecycle/execution-started',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                    ],
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 5,
                        AddEventRequest::KEY_TYPE => 'lifecycle/execution-completed',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                    ],
                    [
                        AddEventRequest::KEY_SEQUENCE_NUMBER => 6,
                        AddEventRequest::KEY_TYPE => 'job/ended',
                        AddEventRequest::KEY_LABEL => md5((string) rand()),
                        AddEventRequest::KEY_REFERENCE => md5((string) rand()),
                        AddEventRequest::KEY_BODY => [
                            'end_state' => 'complete',
                        ],
                    ],
                ],
                'stopState' => 'ended',
                'expectedDispatchedNotificationsCount' => 6,
                'expectedRequestBodiesCreator' => function (JobInterface $job) {
                    return [
                        [
                            'label' => $job->getLabel(),
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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
                            'event_add_url' => 'https://localhost/event/add/' . $job->getToken(),
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

    private function waitUntilJobStateIs(string $jobLabel, string $state): void
    {
        $timeout = 30000;
        $duration = 0;
        $period = 1000;

        $jobState = $this->getJobState($jobLabel);

        while ($state !== $jobState) {
            $jobState = $this->getJobState($jobLabel);

            if ($state !== $jobState) {
                $duration += $period;

                if ($duration >= $timeout) {
                    throw new \RuntimeException('Timed out waiting for "' . $jobLabel . '" to be in "' . $state . '"');
                }

                usleep($period);
            }
        }
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
