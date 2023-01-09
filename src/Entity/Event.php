<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event implements \JsonSerializable
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    private readonly string $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private readonly int $sequenceNumber;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private readonly string $job;

    #[ORM\Column(type: 'string', length: 255)]
    private readonly string $type;

    /**
     * @var null|array<mixed>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private readonly ?array $body;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private readonly Reference $reference;

    /**
     * @var Collection<int, Reference>
     */
    #[ORM\ManyToMany(targetEntity: Reference::class)]
    private Collection $relatedReferences;

    /**
     * @param non-empty-string $id
     * @param array<mixed>     $body
     * @param array<Reference> $relatedReferences
     */
    public function __construct(
        string $id,
        int $sequenceNumber,
        string $job,
        string $type,
        ?array $body,
        Reference $referenceEntity,
        array $relatedReferences = [],
    ) {
        $this->id = $id;
        $this->sequenceNumber = $sequenceNumber;
        $this->job = $job;
        $this->type = $type;
        $this->body = $body;
        $this->reference = $referenceEntity;
        $this->relatedReferences = new ArrayCollection($relatedReferences);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'sequence_number' => $this->sequenceNumber,
            'job' => $this->job,
            'type' => $this->type,
        ];

        if (is_array($this->body)) {
            $data['body'] = $this->body;
        }

        if (0 !== count($this->relatedReferences)) {
            $serializedRelatedReferences = [];

            foreach ($this->relatedReferences as $relatedReference) {
                $serializedRelatedReferences[] = $relatedReference->toArray();
            }

            $data['related_references'] = $serializedRelatedReferences;
        }

        return array_merge(
            $data,
            $this->reference->toArray(),
        );
    }

    /**
     * @return null|array<mixed>
     */
    public function getBody(): ?array
    {
        return $this->body;
    }
}
