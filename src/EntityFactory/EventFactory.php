<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Event;
use App\ObjectFactory\UlidFactory;
use App\Repository\EventRepository;

class EventFactory
{
    public function __construct(
        private readonly EventRepository $repository,
        private readonly ReferenceFactory $referenceFactory,
        private readonly UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param non-empty-string  $jobLabel
     * @param positive-int      $sequenceNumber
     * @param non-empty-string  $type
     * @param null|array<mixed> $body
     * @param non-empty-string  $label
     * @param non-empty-string  $reference
     * @param null|array<mixed> $relatedReferences
     */
    public function create(
        string $jobLabel,
        int $sequenceNumber,
        string $type,
        string $label,
        string $reference,
        ?array $body,
        ?array $relatedReferences,
    ): Event {
        $event = $this->repository->findOneBy([
            'job' => $jobLabel,
            'sequenceNumber' => $sequenceNumber,
        ]);

        if (null === $event) {
            $referenceEntity = $this->referenceFactory->create($label, $reference);

            $relatedReferenceEntities = is_array($relatedReferences)
                ? $this->referenceFactory->createFromArrayCollection($relatedReferences)
                : [];

            $event = new Event(
                $this->ulidFactory->create(),
                $sequenceNumber,
                $jobLabel,
                $type,
                $body,
                $referenceEntity,
                $relatedReferenceEntities,
            );

            $this->repository->add($event);
        }

        return $event;
    }
}
