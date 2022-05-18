<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\UniqueConstraint(name: 'job_label_idx', columns: ['job_label'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
class Token
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $jobLabel;

    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    public function __construct(string $jobLabel, string $userId)
    {
        $this->jobLabel = $jobLabel;
        $this->userId = $userId;
    }

    public function getJobLabel(): ?string
    {
        return $this->jobLabel;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
