<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Event;
use App\Entity\Reference;
use App\Repository\EventRepository;

class EventFactory
{
    public function __construct(
        private readonly EventRepository $repository,
        private readonly ReferenceFactory $referenceFactory,
    ) {
    }

    /**
     * @param non-empty-string $jobLabel
     * @param positive-int     $sequenceNumber
     * @param non-empty-string $type
     * @param array<mixed>     $payload
     */
    public function create(
        string $jobLabel,
        int $sequenceNumber,
        string $type,
        Reference $reference,
        array $payload,
    ): Event {
        $event = $this->repository->findOneBy([
            'job' => $jobLabel,
            'sequenceNumber' => $sequenceNumber,
        ]);

        if (null === $event) {
            $reference = $this->referenceFactory->create($reference->getLabel(), $reference->getReference());
            $event = new Event($sequenceNumber, $jobLabel, $type, $payload, $reference);

            $this->repository->add($event);
        }

        return $event;
    }
}
