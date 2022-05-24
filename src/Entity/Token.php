<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\UniqueConstraint(name: 'job_label_idx', columns: ['job_label'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
class Token
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $token;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    private string $jobLabel;

    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    public function __construct(string $jobLabel, string $userId)
    {
        $this->token = (string) new Ulid();
        $this->jobLabel = $jobLabel;
        $this->userId = $userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getJobLabel(): string
    {
        return $this->jobLabel;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
