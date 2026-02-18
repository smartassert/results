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
                'token' => $job->token,
                'state' => $job->state,
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

    public function createEventResponse(EventInterface $event, bool $hasBodyValue): ResponseInterface
    {
        $data = $event->toArray();
        if ([] === $data['body'] && false === $hasBodyValue) {
            unset($data['body']);
        }

        return new Response(
            200,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($data),
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
}
