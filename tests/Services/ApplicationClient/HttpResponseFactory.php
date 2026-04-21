<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\ResultsClient\Model\EventInterface;
use SmartAssert\ResultsClient\Model\Job;
use SmartAssert\ResultsClient\Model\JobState;

class HttpResponseFactory
{
    public function createJobResponse(Job $job): ResponseInterface
    {
        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $job->label,
                'event_add_url' => '/event/add/' . $job->authenticator,
                'state' => $job->state,
                'meta_state' => [
                    'ended' => false,
                    'succeeded' => false,
                ],
            ]),
        );
    }

    public function createJobStatusResponse(JobState $jobState): ResponseInterface
    {
        $responseData = [
            'state' => $jobState->state,
            'meta_state' => [
                'ended' => $jobState->metaState->ended,
                'succeeded' => $jobState->metaState->succeeded,
            ],
        ];

        if ($jobState->hasEndState()) {
            $responseData['end_state'] = $jobState->endState;
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
