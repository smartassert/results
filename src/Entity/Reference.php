<?php

namespace App\Entity;

use App\Repository\ReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferenceRepository::class)]
#[ORM\UniqueConstraint(name: 'label_reference_unique', columns: ['label', 'reference'])]
class Reference
{
    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $label;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(length: 255)]
    private string $reference;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'bigint')]
    private int $id;

    /**
     * @param non-empty-string $label
     * @param non-empty-string $reference
     */
    public function __construct(string $label, string $reference)
    {
        $this->label = $label;
        $this->reference = $reference;
    }

    /**
     * @return non-empty-string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return non-empty-string
     */
    public function getReference(): string
    {
        return $this->reference;
    }
}
