<?php

namespace App\Controller;

use App\Entity\Token;
use App\Repository\TokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TokenController
{
    #[Route('/token/{job_label<[A-Z0-9]{26,32}>}', name: 'token_create', methods: ['POST'])]
    public function create(TokenRepository $tokenRepository, string $job_label): Response
    {
        /**
         * @todo Inject UserInterface instance into action in #7
         */
        $userId = md5('user id');
        $token = $tokenRepository->findOneBy(['jobLabel' => $job_label]);

        if (null === $token) {
            $token = new Token($job_label, $userId);
            $tokenRepository->add($token);
        }

        if ($userId !== $token->getUserId()) {
            return new JsonResponse(null, 403);
        }

        return new JsonResponse([
            'token' => $token->getToken(),
        ]);
    }
}
