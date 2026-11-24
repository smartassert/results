<?php

namespace App\RemoteEvent;

use App\EntityFactory\EventFactory;
use App\Enum\RemoteEventType;
use App\Event\JobStateChangedEvent;
use App\Event\WorkerEventCreatedEvent;
use App\ObjectFactory\JobStateFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer(RemoteEventType::WORKER_EVENT->value)]
final class WorkerEventWebhookConsumer implements ConsumerInterface
{
    public function __construct(
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

        $currentJobState = $this->jobStateFactory->create($event->job->getLabel());

        $eventEntity = $this->eventFactory->createFromRequest($event->addEventRequest);

        $this->eventDispatcher->dispatch(new WorkerEventCreatedEvent($eventEntity));

        $newJobState = $this->jobStateFactory->create($event->job->getLabel());

        if ($newJobState->getState() !== $currentJobState->getState()) {
            $this->eventDispatcher->dispatch(new JobStateChangedEvent($event->job, $newJobState));
        }
    }
}
