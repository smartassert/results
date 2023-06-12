<?php

namespace App\Controller;

use App\Entity\Job;
use App\EntityFactory\JobFactory;
use App\Exception\EmptyUlidException;
use App\Exception\InvalidUserException;
use App\ObjectFactory\JobStateFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class JobController
{
    /**
     * @param non-empty-string $label
     *
     * @throws EmptyUlidException
     */
    #[Route('/job/{label<[A-Z0-9]{26,32}>}', name: 'job_create', methods: ['POST'])]
    public function create(JobFactory $jobFactory, UserInterface $user, string $label): Response
    {
        try {
            return new JsonResponse($jobFactory->createForUserAndJob($user, $label)->toArray());
        } catch (InvalidUserException) {
            return new JsonResponse(null, 403);
        }
    }

    #[Route('/job/{label<[A-Z0-9]{26,32}>}', name: 'job_status', methods: ['GET'])]
    public function status(UserInterface $user, JobStateFactory $jobStateFactory, ?Job $job): Response
    {
        if (null === $job || $job->userId !== $user->getUserIdentifier()) {
            return new Response(null, 404);
        }

        return new JsonResponse($jobStateFactory->create($job));
    }
}
