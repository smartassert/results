<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Job;
use App\Exception\InvalidUserException;
use App\Repository\JobRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class JobFactory
{
    public function __construct(
        private readonly JobRepository $repository,
    ) {
    }

    /**
     * @param non-empty-string $jobLabel
     *
     * @throws InvalidUserException
     */
    public function createForUserAndJob(UserInterface $user, string $jobLabel): Job
    {
        $job = $this->repository->findOneBy(['jobLabel' => $jobLabel]);

        if (null === $job) {
            $userIdentifier = trim($user->getUserIdentifier());

            if ('' === $userIdentifier) {
                throw InvalidUserException::createForEmptyUserIdentifier($user);
            }

            $job = new Job($jobLabel, $userIdentifier);
            $this->repository->add($job);
        }

        return $job;
    }
}
