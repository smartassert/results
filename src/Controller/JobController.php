<?php

namespace App\Controller;

use App\Entity\JobInterface;
use App\EntityFactory\JobFactory as JobEntityFactory;
use App\Exception\InvalidUserException;
use App\ObjectFactory\JobFactoryInterface as JobModelFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/job/{label<[A-Z0-9]{26,32}>}', name: 'job_')]
readonly class JobController
{
    public function __construct(
        private JobModelFactory $jobModelFactory,
    ) {}

    /**
     * @param non-empty-string $label
     */
    #[Route(name: 'create', methods: ['POST'])]
    public function create(
        JobEntityFactory $jobEntityFactory,
        UserInterface $user,
        string $label
    ): Response {
        try {
            $jobEntity = $jobEntityFactory->createForUserAndJob($user, $label);

            return new JsonResponse($this->jobModelFactory->create($jobEntity));
        } catch (InvalidUserException) {
            return new JsonResponse(null, 403);
        }
    }

    #[Route(name: 'get', methods: ['GET'])]
    public function get(UserInterface $user, ?JobInterface $job): Response
    {
        if (null === $job || $job->getUserId() !== $user->getUserIdentifier()) {
            return new Response(null, 404);
        }

        return new JsonResponse($this->jobModelFactory->create($job));
    }
}
