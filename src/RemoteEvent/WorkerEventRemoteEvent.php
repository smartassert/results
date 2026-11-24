<?php

namespace App\RemoteEvent;

use App\Entity\JobInterface;
use App\Request\AddEvent\ValidatedRequest;
use Symfony\Component\RemoteEvent\RemoteEvent;

class WorkerEventRemoteEvent extends RemoteEvent
{
    /**
     * @param array<mixed> $payload
     */
    public function __construct(
        string $name,
        string $id,
        array $payload,
        public readonly JobInterface $job,
        public readonly ValidatedRequest $addEventRequest,
    ) {
        parent::__construct($name, $id, $payload);
    }
}
