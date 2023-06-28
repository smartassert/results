<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Reference;
use App\Repository\ReferenceRepository;

class ReferenceFactory
{
    public function __construct(
        private readonly ReferenceRepository $repository,
    ) {
    }

    /**
     * @param non-empty-string $label
     * @param non-empty-string $reference
     */
    public function create(string $label, string $reference): Reference
    {
        $entity = $this->repository->find(Reference::generateId($label, $reference));

        if (null === $entity) {
            $entity = new Reference($label, $reference);
            $this->repository->add($entity);
        }

        return $entity;
    }

    /**
     * @param array<mixed> $collection
     *
     * @return array<Reference>
     */
    public function createFromArrayCollection(array $collection): array
    {
        $entities = [];

        foreach ($collection as $referenceData) {
            if (is_array($referenceData)) {
                $entity = $this->createFromArray($referenceData);

                if ($entity instanceof Reference) {
                    $entities[] = $entity;
                }
            }
        }

        return $entities;
    }

    /**
     * @param array<mixed> $data
     */
    private function createFromArray(array $data): ?Reference
    {
        $label = $data['label'] ?? null;
        $label = is_string($label) ? $label : null;
        $label = '' !== $label ? $label : null;

        $reference = $data['reference'] ?? null;
        $reference = is_string($reference) ? $reference : null;
        $reference = '' !== $reference ? $reference : null;

        return is_string($label) && is_string($reference) ? $this->create($label, $reference) : null;
    }
}
