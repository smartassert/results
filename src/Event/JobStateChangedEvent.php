<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\JobInterface;
use App\Model\JobState;
use Symfony\Contracts\EventDispatcher\Event;

final class JobStateChangedEvent extends Event
{
    public function __construct(
        public readonly JobInterface $job,
        public readonly JobState $state,
    ) {}
}
