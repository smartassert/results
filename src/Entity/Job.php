<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\UniqueConstraint(name: 'label_idx', columns: ['label'])]
#[ORM\UniqueConstraint(name: 'token_idx', columns: ['token'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
class Job implements JobInterface
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    private readonly string $token;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    private readonly string $label;

    #[ORM\Column(type: 'string', length: 32)]
    private readonly string $userId;

    #[ORM\Column(nullable: true)]
    private ?string $notifyUrl;

    /**
     * @param non-empty-string $token
     * @param non-empty-string $label
     * @param non-empty-string $userId
     */
    public function __construct(string $token, string $label, string $userId, ?string $notifyUrl = null)
    {
        $this->token = $token;
        $this->label = $label;
        $this->userId = $userId;
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * @return non-empty-string
     */
    public function getToken(): string
    {
        \assert('' !== $this->token);

        return $this->token;
    }

    /**
     * @return non-empty-string
     */
    public function getLabel(): string
    {
        \assert('' !== $this->label);

        return $this->label;
    }

    /**
     * @return non-empty-string
     */
    public function getUserId(): string
    {
        \assert('' !== $this->userId);

        return $this->userId;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }
}
