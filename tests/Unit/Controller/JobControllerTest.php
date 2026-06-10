<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job as JobEntity;
use App\Entity\JobInterface;
use App\Enum\JobState as JobStateEnum;
use App\Model\Job as JobModel;
use App\ObjectFactory\JobFactoryInterface as JobModelFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class JobControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testStatusNoJob(): void
    {
        $response = new JobController(
            \Mockery::mock(JobModelFactory::class),
        )->get(
            \Mockery::mock(UserInterface::class),
            null
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusInvalidUser(): void
    {
        $job = new JobEntity('token', 'label', 'job-user-id');

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('user-id')
        ;

        $response = new JobController(\Mockery::mock(JobModelFactory::class))->get($user, $job);

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param callable(JobInterface): JobModelFactory $jobModelFactoryCreator
     * @param callable(JobInterface): array<mixed>    $expectedCreator
     */
    #[DataProvider('getStatusSuccessDataProvider')]
    public function testGetStatusSuccess(callable $jobModelFactoryCreator, callable $expectedCreator): void
    {
        $job = new JobEntity('token', 'label', 'job-user-id');

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('job-user-id')
        ;

        $jobModelFactory = $jobModelFactoryCreator($job);

        $response = new JobController($jobModelFactory)->get($user, $job);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertIsArray($responseData);
        self::assertEquals($expectedCreator($job), $responseData);
    }

    /**
     * @return array<mixed>
     */
    public static function getStatusSuccessDataProvider(): array
    {
        return [
            'awaiting events' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::AWAITING_EVENTS,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'awaiting-events',
                        'meta_state' => [
                            'pending' => true,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'started' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::STARTED,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'started',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'compiling' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::COMPILING,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'compiling',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'compiled' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::COMPILED,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'compiled',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'executing' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::EXECUTING,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'executing',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'executed' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::EXECUTED,
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'executed',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => false,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'ended, complete' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::ENDED,
                                'complete',
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'ended',
                        'end_state' => 'complete',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => true,
                        ],
                    ];
                },
            ],
            'ended, timed out' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::ENDED,
                                'timed-out',
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'ended',
                        'end_state' => 'timed-out',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
            'ended, failed/test/failure' => [
                'jobModelFactoryCreator' => function (JobInterface $job) {
                    $jobModelFactory = \Mockery::mock(JobModelFactory::class);
                    $jobModelFactory
                        ->shouldReceive('create')
                        ->andReturn(
                            new JobModel(
                                $job->getLabel(),
                                '/event/add/token',
                                JobStateEnum::ENDED,
                                'failed/test/failure'
                            )
                        )
                    ;

                    return $jobModelFactory;
                },
                'expectedCreator' => function (JobInterface $job) {
                    return [
                        'label' => $job->getLabel(),
                        'event_add_url' => '/event/add/token',
                        'state' => 'ended',
                        'end_state' => 'failed/test/failure',
                        'meta_state' => [
                            'pending' => false,
                            'ended' => true,
                            'succeeded' => false,
                        ],
                    ];
                },
            ],
        ];
    }
}
