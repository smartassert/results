<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\JobController;
use App\Entity\Job as JobEntity;
use App\ObjectFactory\JobFactoryInterface as JobModelFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
}
