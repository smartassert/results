<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\UniqueConstraint(name: 'label_idx', columns: ['label'])]
#[ORM\UniqueConstraint(name: 'token_idx', columns: ['token'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
class Job implements \JsonSerializable
{
    public const ID_LENGTH = 32;

    /**
     * @var non-empty-string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    public readonly string $token;

    /**
     * @var non-empty-string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    public readonly string $label;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: 32)]
    public readonly string $userId;

    /**
     * @param non-empty-string $token
     * @param non-empty-string $label
     * @param non-empty-string $userId
     */
    public function __construct(string $token, string $label, string $userId)
    {
        $this->token = $token;
        $this->label = $label;
        $this->userId = $userId;
    }

    /**
     * @return array{label: non-empty-string, token: non-empty-string}
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'token' => $this->token,
        ];
    }
}
