<?php

namespace App\RemoteEvent;

use App\Entity\Job;
use App\EntityFactory\EventFactory;
use App\Event\JobStateChangedEvent;
use App\Event\WorkerEventCreatedEvent;
use App\ObjectFactory\JobStateFactory;
use App\Repository\JobRepository;
use App\Request\AddEvent\Factory;
use App\Request\AddEvent\InvalidRequestException;
use App\Request\AddEvent\Validator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('worker.event')]
final class WorkerEventWebhookConsumer implements ConsumerInterface
{
    public const string NAME = 'worker.event';

    public function __construct(
        private readonly Factory $requestFactory,
        private readonly Validator $requestValidator,
        private readonly JobRepository $jobRepository,
        private readonly JobStateFactory $jobStateFactory,
        private readonly EventFactory $eventFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function consume(RemoteEvent $event): void
    {
        if (self::NAME !== $event->getName()) {
            return;
        }

        $addEventRequest = $this->requestFactory->create($event->getPayload());

        try {
            $validatedRequest = $this->requestValidator->validate($addEventRequest);
        } catch (InvalidRequestException) {
            return;
        }

        $job = $this->jobRepository->findOneBy(['label' => $validatedRequest->job]);
        if (!$job instanceof Job) {
            return;
        }

        $currentJobState = $this->jobStateFactory->create($job->getLabel());

        $event = $this->eventFactory->createFromRequest($validatedRequest);

        $this->eventDispatcher->dispatch(new WorkerEventCreatedEvent($event));

        $newJobState = $this->jobStateFactory->create($job->getLabel());

        if ($newJobState->getState() !== $currentJobState->getState()) {
            $this->eventDispatcher->dispatch(new JobStateChangedEvent($job, $newJobState));
        }
    }
}
