<?php

namespace App\RemoteEvent;

use App\Entity\JobInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

class WorkerEventRemoteEvent extends RemoteEvent
{
    public function __construct(
        string $name,
        string $id,
        array $payload,
        public readonly JobInterface $job,
    ) {
        parent::__construct($name, $id, $payload);
    }
}
