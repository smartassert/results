<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\EntityFactory\TokenFactory;
use App\Exception\InvalidUserException;
use App\Repository\TokenRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenFactoryTest extends TestCase
{
    public function testCreateThrowsInvalidUserException(): void
    {
        $jobLabel = md5((string) rand());

        $tokenRepository = \Mockery::mock(TokenRepository::class);
        $tokenRepository
            ->shouldReceive('findOneBy')
            ->with(['jobLabel' => $jobLabel])
            ->andReturnNull()
        ;

        $user = \Mockery::mock(UserInterface::class);
        $user
            ->shouldReceive('getUserIdentifier')
            ->andReturn('')
        ;

        $factory = new TokenFactory($tokenRepository);

        self::expectException(InvalidUserException::class);
        self::expectExceptionMessage(InvalidUserException::MESSAGE_USER_IDENTIFIER_EMPTY);
        self::expectExceptionCode(InvalidUserException::CODE_USER_IDENTIFIER_EMPTY);

        $factory->createForUserAndJob($user, $jobLabel);
    }
}
