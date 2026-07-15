<?php

namespace App\Services;

use App\Entity\Job;
use App\Event\JobCreatedEvent;
use App\Repository\JobRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class JobMutator implements EventSubscriberInterface
{
    public function __construct(
        private JobRepository $repository,
    ) {}

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            JobCreatedEvent::class => [
                ['persist', 1000],
            ],
        ];
    }

    public function persist(JobCreatedEvent $event): void
    {
        $job = $event->job;

        if ($job instanceof Job) {
            $this->repository->add($job);
        }
    }
}
