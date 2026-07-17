<?php

declare(strict_types=1);

namespace App\EventDispatcher;

use App\Event\JobStateChangedEvent;
use App\Event\NotifiableJobStateChangedEvent;
use App\ObjectFactory\SerializableJobFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class NotifiableEventDispatcher implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private SerializableJobFactoryInterface $serializableJobFactory,
    ) {}

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JobStateChangedEvent::class => [
                ['dispatchForJobStateChangedEvent', 0],
            ],
        ];
    }

    public function dispatchForJobStateChangedEvent(JobStateChangedEvent $event): void
    {
        $serializableJob = $this->serializableJobFactory->create($event->job);

        $notifiableEvent = new NotifiableJobStateChangedEvent(
            $event->job,
            $serializableJob->toArray(),
        );

        $this->eventDispatcher->dispatch($notifiableEvent);
    }
}
