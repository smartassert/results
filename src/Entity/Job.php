<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\UniqueConstraint(name: 'label_idx', columns: ['label'])]
#[ORM\UniqueConstraint(name: 'token_idx', columns: ['token'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
class Job
{
    public const ID_LENGTH = 32;

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
     * @param non-empty-string $label
     * @param non-empty-string $userId
     */
    public function __construct(string $label, string $userId)
    {
        $this->token = (string) new Ulid();
        $this->label = $label;
        $this->userId = $userId;
    }
}
