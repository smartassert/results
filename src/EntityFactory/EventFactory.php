<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Event;
use App\Repository\EventRepository;

class EventFactory
{
    public function __construct(
        private readonly EventRepository $repository,
    ) {
    }

    /**
     * @param non-empty-string $jobLabel
     * @param positive-int     $sequenceNumber
     * @param non-empty-string $type
     * @param non-empty-string $label
     * @param non-empty-string $reference
     * @param array<mixed>     $payload
     */
    public function create(
        string $jobLabel,
        int $sequenceNumber,
        string $type,
        string $label,
        string $reference,
        array $payload,
    ): Event {
        $event = $this->repository->findOneBy([
            'job' => $jobLabel,
            'sequenceNumber' => $sequenceNumber,
        ]);

        if (null === $event) {
            $event = new Event($sequenceNumber, $jobLabel, $type, $label, $reference, $payload);
            $this->repository->add($event);
        }

        return $event;
    }
}
