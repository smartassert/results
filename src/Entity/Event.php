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
    protected string $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $identifier;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $label;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $type;

    #[ORM\Column(type: 'string', length: self::ID_LENGTH)]
    private string $reference;

    /**
     * @var array<mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

    /**
     * @param array<mixed> $payload
     */
    public function __construct(int $identifier, string $label, string $type, string $reference, array $payload)
    {
        $this->id = (string) new Ulid();
        $this->identifier = $identifier;
        $this->label = $label;
        $this->type = $type;
        $this->reference = $reference;
        $this->payload = $payload;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'label' => $this->label,
            'type' => $this->type,
            'reference' => $this->reference,
            'payload' => $this->payload,
        ];
    }
}
