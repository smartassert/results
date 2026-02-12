<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job;
use App\Enum\JobState as JobStateEnum;
use App\Model\JobState;
use App\ObjectFactory\JobStateFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class JobControllerTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testStatusNoJob(): void
    {
        $controller = new JobController(\Mockery::mock(JobStateFactory::class));
        $response = $controller->status(\Mockery::mock(UserInterface::class), null);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusInvalidUser(): void
    {
        $job = new Job('token', 'label', 'job-user-id');

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('user-id')
        ;

        $controller = new JobController(\Mockery::mock(JobStateFactory::class));

        $response = $controller->status($user, $job);

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param array<mixed> $expected
     */
    #[DataProvider('getStatusSuccessDataProvider')]
    public function testGetStatusSuccess(JobStateFactory $jobStateFactory, array $expected): void
    {
        $job = new Job('token', 'label', 'job-user-id');

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('job-user-id')
        ;

        $controller = new JobController($jobStateFactory);
        $response = $controller->status($user, $job);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertIsArray($responseData);
        self::assertSame($expected, $responseData);
    }

    /**
     * @return array<mixed>
     */
    public static function getStatusSuccessDataProvider(): array
    {
        return [
            'awaiting events' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::AWAITING_EVENTS,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'awaiting-events',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'started' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::STARTED,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'started',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'compiling' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::COMPILING,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'compiling',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'compiled' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::COMPILED,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'compiled',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'executing' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::EXECUTING,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'executing',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'executed' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::EXECUTED,
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'executed',
                    'meta_state' => [
                        'ended' => false,
                        'succeeded' => false,
                    ],
                ],
            ],
            'ended, complete' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::ENDED,
                            'complete'
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'ended',
                    'meta_state' => [
                        'ended' => true,
                        'succeeded' => true,
                    ],
                    'end_state' => 'complete',
                ],
            ],
            'ended, timed out' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::ENDED,
                            'timed-out'
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'ended',
                    'meta_state' => [
                        'ended' => true,
                        'succeeded' => false,
                    ],
                    'end_state' => 'timed-out',
                ],
            ],
            'ended, failed/test/failure' => [
                'jobStateFactory' => (function () {
                    $jobStateFactory = \Mockery::mock(JobStateFactory::class);
                    $jobStateFactory
                        ->shouldReceive('create')
                        ->andReturn(new JobState(
                            JobStateEnum::ENDED,
                            'failed/test/failure'
                        ))
                    ;

                    return $jobStateFactory;
                })(),
                'expected' => [
                    'state' => 'ended',
                    'meta_state' => [
                        'ended' => true,
                        'succeeded' => false,
                    ],
                    'end_state' => 'failed/test/failure',
                ],
            ],
        ];
    }
}
