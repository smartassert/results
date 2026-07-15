<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\JobInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class JobCreatedEvent extends Event
{
    public function __construct(
        public readonly JobInterface $job,
    ) {}
}
