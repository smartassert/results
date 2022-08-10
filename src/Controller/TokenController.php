<?php

namespace App\Controller;

use App\EntityFactory\JobFactory;
use App\Exception\InvalidUserException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenController
{
    /**
     * @param non-empty-string $job_label
     */
    #[Route('/token/{job_label<[A-Z0-9]{26,32}>}', name: 'token_create', methods: ['POST'])]
    public function create(JobFactory $jobFactory, UserInterface $user, string $job_label): Response
    {
        try {
            return new JsonResponse([
                'token' => $jobFactory->createForUserAndJob($user, $job_label)->token,
            ]);
        } catch (InvalidUserException) {
            return new JsonResponse(null, 403);
        }
    }
}
