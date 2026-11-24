<?php

namespace App\RemoteEvent;

use App\EntityFactory\EventFactory;
use App\Enum\RemoteEventType;
use App\Event\JobStateChangedEvent;
use App\Event\WorkerEventCreatedEvent;
use App\ObjectFactory\JobStateFactory;
use App\Request\AddEvent\Factory;
use App\Request\AddEvent\InvalidRequestException;
use App\Request\AddEvent\Validator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(RemoteEventType::WORKER_EVENT->value)]
final class WorkerEventWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly Factory $requestFactory,
        private readonly Validator $requestValidator,
        private readonly JobStateFactory $jobStateFactory,
        private readonly EventFactory $eventFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function consume(RemoteEvent $event): void
    {
        if (RemoteEventType::WORKER_EVENT->value !== $event->getName()) {
            return;
        }

        if (!$event instanceof WorkerEventRemoteEvent) {
            return;
        }

        $addEventRequest = $this->requestFactory->create($event->getPayload());

        try {
            $validatedRequest = $this->requestValidator->validate($addEventRequest);
        } catch (InvalidRequestException) {
            return;
        }

        $currentJobState = $this->jobStateFactory->create($event->job->getLabel());

        $eventEntity = $this->eventFactory->createFromRequest($validatedRequest);

        $this->eventDispatcher->dispatch(new WorkerEventCreatedEvent($eventEntity));

        $newJobState = $this->jobStateFactory->create($event->job->getLabel());

        if ($newJobState->getState() !== $currentJobState->getState()) {
            $this->eventDispatcher->dispatch(new JobStateChangedEvent($event->job, $newJobState));
        }
    }
}
