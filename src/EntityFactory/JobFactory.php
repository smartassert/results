<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Job;
use App\Exception\EmptyUlidException;
use App\Exception\InvalidUserException;
use App\ObjectFactory\UlidFactory;
use App\Repository\JobRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class JobFactory
{
    public function __construct(
        private readonly JobRepository $repository,
        private readonly UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param non-empty-string $label
     *
     * @throws InvalidUserException
     * @throws EmptyUlidException
     */
    public function createForUserAndJob(UserInterface $user, string $label): Job
    {
        $job = $this->repository->findOneBy(['label' => $label]);

        if (null === $job) {
            $userIdentifier = trim($user->getUserIdentifier());

            if ('' === $userIdentifier) {
                throw InvalidUserException::createForEmptyUserIdentifier($user);
            }

            $job = new Job($this->ulidFactory->create(), $label, $userIdentifier);
            $this->repository->add($job);
        }

        return $job;
    }
}
