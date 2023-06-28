<?php

namespace App\Entity;

use App\Repository\ReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferenceRepository::class)]
#[ORM\UniqueConstraint(name: 'label_reference_unique', columns: ['label', 'reference'])]
class Reference
{
    #[ORM\Id]
    #[ORM\Column(length: 32, unique: true, nullable: false)]
    private string $id;

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

    /**
     * @param non-empty-string $label
     * @param non-empty-string $reference
     */
    public function __construct(string $label, string $reference)
    {
        $this->id = md5($label . $reference);
        $this->label = $label;
        $this->reference = $reference;
    }

    /**
     * @return array{label: non-empty-string, reference: non-empty-string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'reference' => $this->reference,
        ];
    }
}
