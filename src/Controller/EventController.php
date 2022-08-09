<?php

namespace App\Controller;

use App\Entity\Token;
use App\EntityFactory\EventFactory;
use App\Request\AddEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController
{
    #[Route('/event/{token<[A-Z0-9]{26,32}>}', name: 'event_add', methods: ['POST'])]
    public function add(
        EventFactory $eventFactory,
        ?Token $tokenEntity,
        AddEventRequest $request
    ): Response {
        if (null === $tokenEntity) {
            return new Response('', 404);
        }

        if (null === $request->sequenceNumber) {
            return $this->createInvalidAddEventRequestFieldResponse(AddEventRequest::KEY_SEQUENCE_NUMBER, 'an integer');
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

        if (null === $request->payload) {
            return $this->createInvalidAddEventRequestFieldResponse(
                AddEventRequest::KEY_PAYLOAD,
                'a JSON string that decodes to an array'
            );
        }

        $event = $eventFactory->create(
            $tokenEntity->jobLabel,
            $request->sequenceNumber,
            $request->type,
            $request->label,
            $request->reference,
            $request->payload
        );

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
