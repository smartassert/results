<?php

namespace App\Controller;

use App\Entity\Reference;
use App\Repository\EventRepository;
use App\Request\ListEventsRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class EventController
{
    #[Route('/event/list/{label<[A-Z0-9]{26,32}>}', methods: ['GET'])]
    public function list(UserInterface $user, EventRepository $repository, ListEventsRequest $request): JsonResponse
    {
        if (
            null === $request->job
            || $request->job->getUserId() !== $user->getUserIdentifier()
            || $request->hasReferenceFilter && null === $request->reference
        ) {
            return new JsonResponse([]);
        }

        $findCriteria = [
            'job' => $request->job->getLabel(),
        ];

        if ($request->reference instanceof Reference) {
            $findCriteria['reference'] = $request->reference;
        }

        if (is_string($request->type)) {
            $findCriteria['type'] = $request->type;
        }

        return new JsonResponse(
            $repository->findBy($findCriteria, ['sequenceNumber' => 'ASC'])
        );
    }
}
