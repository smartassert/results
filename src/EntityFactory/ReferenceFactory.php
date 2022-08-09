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
    public function create(
        string $label,
        string $reference,
    ): Reference {
        $entity = $this->repository->findOneBy([
            'label' => $label,
            'reference' => $reference,
        ]);

        if (null === $entity) {
            $entity = new Reference($label, $reference);
            $this->repository->add($entity);
        }

        return $entity;
    }
}
