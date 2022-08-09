<?php

namespace App\Exception;

use Symfony\Component\Security\Core\User\UserInterface;

class InvalidUserException extends \Exception
{
    public const MESSAGE_USER_IDENTIFIER_EMPTY = 'User identifier is empty';
    public const CODE_USER_IDENTIFIER_EMPTY = 100;

    public function __construct(
        public readonly UserInterface $user,
        string $message,
        int $code
    ) {
        parent::__construct($message, $code);
    }

    public static function createForEmptyUserIdentifier(UserInterface $user): self
    {
        return new InvalidUserException($user, self::MESSAGE_USER_IDENTIFIER_EMPTY, self::CODE_USER_IDENTIFIER_EMPTY);
    }
}
