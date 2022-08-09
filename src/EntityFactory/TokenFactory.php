<?php

declare(strict_types=1);

namespace App\EntityFactory;

use App\Entity\Token;
use App\Exception\InvalidUserException;
use App\Repository\TokenRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenFactory
{
    public function __construct(
        private readonly TokenRepository $repository,
    ) {
    }

    /**
     * @param non-empty-string $jobLabel
     *
     * @throws InvalidUserException
     */
    public function createForUserAndJob(UserInterface $user, string $jobLabel): Token
    {
        $token = $this->repository->findOneBy(['jobLabel' => $jobLabel]);

        if (null === $token) {
            $userIdentifier = trim($user->getUserIdentifier());

            if ('' === $userIdentifier) {
                throw InvalidUserException::createForEmptyUserIdentifier($user);
            }

            $token = new Token($jobLabel, $user->getUserIdentifier());
            $this->repository->add($token);
        }

        return $token;
    }
}