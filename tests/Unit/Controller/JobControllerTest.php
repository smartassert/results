<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job;
use App\Enum\JobState as JobStateEnum;
use App\Model\JobState;
use App\ObjectFactory\JobStateFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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

    public function testStatusSuccess(): void
    {
        $job = new Job('token', 'label', 'job-user-id');

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('job-user-id')
        ;

        $jobStateFactory = \Mockery::mock(JobStateFactory::class);
        $jobStateFactory
            ->shouldReceive('create')
            ->with($job)
            ->andReturn(new JobState(
                JobStateEnum::ENDED,
                'complete'
            ))
        ;

        $controller = new JobController($jobStateFactory);
        $response = $controller->status($user, $job);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));

        $responseData = json_decode((string) $response->getContent(), true);
        self::assertIsArray($responseData);
        self::assertSame(
            [
                'state' => 'ended',
                'end_state' => 'complete',
            ],
            $responseData
        );
    }
}
