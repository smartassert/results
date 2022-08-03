<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event implements \JsonSerializable
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $sequenceNumber;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $job;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $type;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $reference;

    /**
     * @var array<mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

    #[ORM\Column(type: Types::TEXT)]
    private string $label;

    /**
     * @param array<mixed> $payload
     */
    public function __construct(
        int $sequenceNumber,
        string $job,
        string $type,
        string $label,
        string $reference,
        array $payload
    ) {
        $this->id = (string) new Ulid();
        $this->sequenceNumber = $sequenceNumber;
        $this->job = $job;
        $this->type = $type;
        $this->label = $label;
        $this->reference = $reference;
        $this->payload = $payload;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'sequence_number' => $this->sequenceNumber,
            'job' => $this->job,
            'type' => $this->type,
            'label' => $this->label,
            'reference' => $this->reference,
            'payload' => $this->payload,
        ];
    }
}
