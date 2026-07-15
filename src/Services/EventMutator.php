<?php

namespace App\Services;

use App\Event\WorkerEventCreatedEvent;
use App\Repository\EventRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class EventMutator implements EventSubscriberInterface
{
    public function __construct(
        private EventRepository $repository,
    ) {}

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerEventCreatedEvent::class => [
                ['persist', 1000],
            ],
        ];
    }

    public function persist(WorkerEventCreatedEvent $event): void
    {
        $this->repository->add($event->event);
    }
}
