<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\Job;

readonly class JobFactory
{
    public function __construct(
        private JobStateFactory $jobStateFactory,
    ) {}

    public function create(JobInterface $job): Job
    {
        $jobState = $this->jobStateFactory->create($job);

        return new Job($job->getLabel(), $job->getToken(), $jobState->state, $jobState->endState);
    }
}
