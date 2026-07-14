<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Job;
use App\ObjectFactory\UlidFactory;
use App\Repository\JobRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class JobFactory
{
    public function __construct(
        private readonly JobRepository $repository,
        private readonly UlidFactory $ulidFactory,
    ) {}

    /**
     * @param non-empty-string $label
     */
    public function createForUserAndJob(UserInterface $user, string $label, ?string $notifyUrl): Job
    {
        $job = $this->repository->findOneBy(['label' => $label]);

        if (null === $job) {
            $job = new Job($this->ulidFactory->create(), $label, $user->getUserIdentifier(), $notifyUrl);
            $this->repository->add($job);
        }

        return $job;
    }
}
