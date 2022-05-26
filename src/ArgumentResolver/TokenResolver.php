<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Token;
use App\Repository\TokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class TokenResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly TokenRepository $tokenRepository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Token::class === $argument->getType() && $request->attributes->has('token');
    }

    /**
     * @return \Traversable<?Token>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        $requestToken = $request->attributes->get('token');
        $requestToken = is_string($requestToken) ? trim($requestToken) : '';

        yield '' === $requestToken ? null : $this->tokenRepository->findOneBy(['token' => $requestToken]);
    }
}
