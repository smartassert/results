<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

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
     * @var array<mixed>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private readonly array $body;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private readonly Reference $reference;

    /**
     * @param array<mixed> $body
     */
    public function __construct(
        int $sequenceNumber,
        string $job,
        string $type,
        array $body,
        Reference $referenceEntity,
    ) {
        $this->id = (string) new Ulid();
        $this->sequenceNumber = $sequenceNumber;
        $this->job = $job;
        $this->type = $type;
        $this->body = $body;
        $this->reference = $referenceEntity;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                'sequence_number' => $this->sequenceNumber,
                'job' => $this->job,
                'type' => $this->type,
                'body' => $this->body,
            ],
            $this->reference->toArray(),
        );
    }
}
