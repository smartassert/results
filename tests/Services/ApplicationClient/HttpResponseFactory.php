<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\ResultsClient\Model\CreatedJobInterface;
use SmartAssert\ResultsClient\Model\EventInterface;
use SmartAssert\ResultsClient\Model\JobInterface;

class HttpResponseFactory
{
    public function createJobResponse(JobInterface $job): ResponseInterface
    {
        $responseData = [
            'label' => $job->getLabel(),
            'state' => $job->getState()->state,
            'meta_state' => [
                'pending' => $job->getState()->metaState->pending,
                'ended' => $job->getState()->metaState->ended,
                'succeeded' => $job->getState()->metaState->succeeded,
            ],
            'has_events' => $job->hasEvents(),
            'previous_states' => $job->getPreviousStates(),
        ];

        $endState = $job->getState()->endState;
        if (is_string($endState)) {
            $responseData['end_state'] = $endState;
        }

        if ($job instanceof CreatedJobInterface) {
            $responseData['event_notify'] = [
                'url' => $job->getEventNotifyUrl(),
                'token' => $job->getEventNotifyToken(),
            ];
        }

        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($responseData),
        );
    }

    /**
     * @param EventInterface[] $events
     */
    public function createEventListResponse(array $events): ResponseInterface
    {
        $data = [];

        foreach ($events as $event) {
            $serializedEvent = $event->toArray();
            unset($serializedEvent['body']);

            $data[] = $serializedEvent;
        }

        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($data),
        );
    }

    public function createSuccessResponse(): ResponseInterface
    {
        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ]
        );
    }
}
