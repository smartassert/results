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
        private string $selfUrl,
    ) {}

    public function create(JobInterface $job): Job
    {
        $jobState = $this->jobStateFactory->create($job);
        $relativeUrl = $this->router->generate('event_add', ['token' => $job->getToken()]);
        $eventAddUrl = rtrim($this->selfUrl, '/') . $relativeUrl;

        return new Job($job->getLabel(), $eventAddUrl, $jobState->state, $jobState->endState);
    }
}
