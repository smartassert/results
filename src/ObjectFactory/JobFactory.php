<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\Job;
use App\Repository\EventRepository;
use Symfony\Component\Routing\RouterInterface;

readonly class JobFactory implements JobFactoryInterface
{
    public function __construct(
        private JobStateFactory $jobStateFactory,
        private RouterInterface $router,
        private string $selfUrl,
        private EventRepository $eventRepository,
    ) {}

    public function create(JobInterface $job): Job
    {
        $jobState = $this->jobStateFactory->create($job->getLabel());
        $relativeUrl = $this->router->generate('event_add', ['token' => $job->getToken()]);
        $eventAddUrl = rtrim($this->selfUrl, '/') . $relativeUrl;

        $job = new Job(
            $job->getLabel(),
            $eventAddUrl,
            $jobState->getState(),
            $this->eventRepository->hasForJob($job->getLabel()),
        );

        $endState = $jobState->getEndState();
        if (null !== $endState) {
            $job = $job->withEndState($endState);
        }

        return $job;
    }
}
