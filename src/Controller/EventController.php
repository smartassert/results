<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Token;
use App\Repository\EventRepository;
use App\Request\AddEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController
{
    #[Route('/event/{token<[A-Z0-9]{26,32}>}', name: 'event_add', methods: ['POST'])]
    public function add(EventRepository $eventRepository, ?Token $tokenEntity, AddEventRequest $request): Response
    {
        if (null === $tokenEntity) {
            return new Response('', 404);
        }

        if (null === $request->identifier) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_IDENTIFIER, 'an integer');
        }

        if (null === $request->type) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_TYPE, 'a string');
        }

        if (null === $request->reference) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_REFERENCE, 'a string');
        }

        if (null === $request->payload) {
            return $this->createInvalidAddEventRequestFieldResponse(
                AddEventRequest::KEY_PAYLOAD,
                'a JSON string that decodes to an array'
            );
        }

        $event = $eventRepository->findOneBy([
            'identifier' => $request->identifier,
            'job' => $tokenEntity->getJobLabel(),
        ]);

        if (null === $event) {
            $event = new Event(
                $request->identifier,
                $tokenEntity->getJobLabel(),
                $request->type,
                $request->reference,
                $request->payload
            );

            $eventRepository->add($event);
        }

        return new JsonResponse($event);
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
