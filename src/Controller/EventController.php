<?php

namespace App\Controller;

use App\Entity\Job;
use App\EntityFactory\EventFactory;
use App\Request\AddEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController
{
    #[Route('/event/add/{token<[A-Z0-9]{26,32}>}', name: 'event_add', methods: ['POST'])]
    public function add(EventFactory $eventFactory, ?Job $job, AddEventRequest $request): Response
    {
        if (null === $job) {
            return new Response('', 404);
        }

        if (null === $request->sequenceNumber) {
            return $this->createInvalidAddEventRequestFieldResponse(
                AddEventRequest::KEY_SEQUENCE_NUMBER,
                'a positive integer'
            );
        }

        if (null === $request->type) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_TYPE, 'a string');
        }

        if (null === $request->label) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_LABEL, 'a string');
        }

        if (null === $request->reference) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_REFERENCE, 'a string');
        }

        $event = $eventFactory->create(
            $job->label,
            $request->sequenceNumber,
            $request->type,
            $request->label,
            $request->reference,
            $request->body,
            $request->relatedReferences,
        );

        return new JsonResponse($event);
    }

    #[Route('/event/list/{job<[A-Z0-9]{26,32}>}', name: 'event_list', methods: ['GET'])]
    public function list(string $job): JsonResponse
    {
        return new JsonResponse();
    }

    private function createInvalidAddEventRequestFieldResponse(string $field, string $expectedFormat): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => [
                    'type' => 'invalid_request',
                    'payload' => [
                        $field => [
                            'value' => null,
                            'message' => sprintf(
                                'Required field "%s" invalid, missing from request or not %s.',
                                $field,
                                $expectedFormat
                            ),
                        ],
                    ],
                ],
            ],
            400
        );
    }
}
