<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\SerializableCreatedJob;
use App\Model\SerializableJob;
use App\Model\SerializableJobInterface;
use App\Repository\EventRepository;
use Symfony\Component\Routing\RouterInterface;

readonly class SerializableJobFactory implements SerializableJobFactoryInterface
{
    public function __construct(
        private JobStateFactory $jobStateFactory,
        private RouterInterface $router,
        private string $selfUrl,
        private EventRepository $eventRepository,
    ) {}

    public function createForNewJob(JobInterface $job): SerializableJobInterface
    {
        $jobState = $this->jobStateFactory->create($job->getLabel());

        $relativeUrl = $this->router->generate('app_event_add', ['token' => $job->getToken()]);
        $eventAddUrl = rtrim($this->selfUrl, '/') . $relativeUrl;
        $eventNotifyUrl = rtrim($this->selfUrl, '/')
            . $this->router->generate('_webhook_controller', ['type' => 'worker.event']);

        return new SerializableCreatedJob(
            $job->getLabel(),
            $jobState->getState(),
            $eventAddUrl,
            $job->getToken(),
            $eventNotifyUrl,
        );
    }

    public function createForExistingJob(JobInterface $job): SerializableJobInterface
    {
        $jobState = $this->jobStateFactory->create($job->getLabel());

        return new SerializableJob(
            $job->getLabel(),
            $jobState->getState(),
            $this->eventRepository->hasForJob($job->getLabel()),
            $jobState->getEndState(),
        );
    }
}
