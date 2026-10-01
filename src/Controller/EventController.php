<?php

namespace App\Controller;

use App\Entity\JobInterface;
use App\Entity\Reference;
use App\EntityFactory\EventFactory;
use App\Event\JobStateChangedEvent;
use App\Event\WorkerEventCreatedEvent;
use App\ObjectFactory\JobStateFactory;
use App\Repository\EventRepository;
use App\Request\AddEvent\InvalidRequestException;
use App\Request\AddEvent\Request;
use App\Request\AddEvent\Validator;
use App\Request\ListEventsRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class EventController
{
    #[Route('/event/add/{token<[A-Z0-9]{26,32}>}', methods: ['POST'])]
    public function add(
        Validator $requestValidator,
        EventFactory $eventFactory,
        JobStateFactory $jobStateFactory,
        EventDispatcherInterface $eventDispatcher,
        Request $request,
        ?JobInterface $job
    ): Response {
        if (null === $job) {
            return new Response('', 404);
        }

        try {
            $validatedRequest = $requestValidator->validate($request);
        } catch (InvalidRequestException $e) {
            return $this->createInvalidAddEventRequestFieldResponse($e->field, $e->getMessage());
        }

        $currentJobState = $jobStateFactory->create($job->getLabel());

        $event = $eventFactory->create(
            $job->getLabel(),
            $validatedRequest->sequenceNumber,
            $validatedRequest->type,
            $validatedRequest->label,
            $validatedRequest->reference,
            $validatedRequest->body,
            $validatedRequest->relatedReferences,
        );

        $eventDispatcher->dispatch(new WorkerEventCreatedEvent($event));

        $newJobState = $jobStateFactory->create($job->getLabel());

        if ($newJobState->getState() !== $currentJobState->getState()) {
            $eventDispatcher->dispatch(new JobStateChangedEvent($job, $newJobState));
        }

        return new Response();
    }

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

    private function createInvalidAddEventRequestFieldResponse(string $field, string $message): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        $field => [
                            'value' => null,
                            'message' => $message,
                        ],
                    ],
                ],
            ],
            400
        );
    }
}
