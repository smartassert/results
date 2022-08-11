<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\UniqueConstraint(name: 'job_label_idx', columns: ['job_label'])]
#[ORM\UniqueConstraint(name: 'job_token_idx', columns: ['token'])]
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
    public readonly string $jobLabel;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private readonly string $userId;

    /**
     * @param non-empty-string $jobLabel
     * @param non-empty-string $userId
     */
    public function __construct(string $jobLabel, string $userId)
    {
        $this->token = (string) new Ulid();
        $this->jobLabel = $jobLabel;
        $this->userId = $userId;
    }
}
