<?php

namespace App\Controller;

use App\Entity\JobInterface;
use App\EntityFactory\JobFactory as JobEntityFactory;
use App\Exception\InvalidUserException;
use App\ObjectFactory\JobFactory as JobModelFactory;
use App\ObjectFactory\JobStateFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class JobController
{
    /**
     * @param non-empty-string $label
     */
    #[Route('/job/{label<[A-Z0-9]{26,32}>}', name: 'job_create', methods: ['POST'])]
    public function create(
        JobEntityFactory $jobEntityFactory,
        JobModelFactory $jobModelFactory,
        UserInterface $user,
        string $label
    ): Response {
        try {
            $jobEntity = $jobEntityFactory->createForUserAndJob($user, $label);

            return new JsonResponse($jobModelFactory->create($jobEntity));
        } catch (InvalidUserException) {
            return new JsonResponse(null, 403);
        }
    }

    #[Route('/job/{label<[A-Z0-9]{26,32}>}', name: 'job_status', methods: ['GET'])]
    public function status(JobStateFactory $jobStateFactory, UserInterface $user, ?JobInterface $job): Response
    {
        if (null === $job || $job->getUserId() !== $user->getUserIdentifier()) {
            return new Response(null, 404);
        }

        return new JsonResponse($jobStateFactory->create($job));
    }
}
