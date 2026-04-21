<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\Job;
use Symfony\Component\Routing\RouterInterface;

readonly class JobFactory
{
    public function __construct(
        private JobStateFactory $jobStateFactory,
        private RouterInterface $router,
    ) {}

    public function create(JobInterface $job): Job
    {
        $jobState = $this->jobStateFactory->create($job);
        $eventAddUrl = $this->router->generate('event_add', ['token' => $job->getToken()]);

        return new Job($job->getLabel(), $eventAddUrl, $jobState->state, $jobState->endState);
    }
}
