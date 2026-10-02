<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Event;
use App\ObjectFactory\UlidFactory;
use App\Repository\EventRepository;
use App\Request\AddEvent\ValidatedRequest;

readonly class EventFactory
{
    public function __construct(
        private EventRepository $repository,
        private ReferenceFactory $referenceFactory,
        private UlidFactory $ulidFactory,
    ) {}

    public function createFromRequest(ValidatedRequest $request): Event
    {
        return $this->create(
            $request->job,
            $request->sequenceNumber,
            $request->type,
            $request->label,
            $request->reference,
            $request->body,
            $request->relatedReferences,
        );
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

        if ($event instanceof Event) {
            return $event;
        }

        $referenceEntity = $this->referenceFactory->create($label, $reference);

        $relatedReferenceEntities = is_array($relatedReferences)
            ? $this->referenceFactory->createFromArrayCollection($relatedReferences)
            : [];

        return new Event(
            $this->ulidFactory->create(),
            $sequenceNumber,
            $jobLabel,
            $type,
            $body,
            $referenceEntity,
            $relatedReferenceEntities,
        );
    }
}
