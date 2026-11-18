<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
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

    public function createWithoutToken(JobInterface $job): SerializableJobInterface
    {
        $jobState = $this->jobStateFactory->create($job->getLabel());
        $eventAddUrl = 'empty';

        $job = new SerializableJob(
            $job->getLabel(),
            null,
            $eventAddUrl,
            null,
            $jobState->getState(),
            $this->eventRepository->hasForJob($job->getLabel()),
        );

        $endState = $jobState->getEndState();
        if (null !== $endState) {
            $job = $job->withEndState($endState);
        }

        return $job;
    }

    public function create(JobInterface $job): SerializableJobInterface
    {
        $jobState = $this->jobStateFactory->create($job->getLabel());
        $eventAddUrl = 'empty';
        $eventNotifyUrl = rtrim($this->selfUrl, '/')
            . $this->router->generate('_webhook_controller', ['type' => 'worker.event']);

        $job = new SerializableJob(
            $job->getLabel(),
            $job->getToken(),
            $eventAddUrl,
            $eventNotifyUrl,
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
