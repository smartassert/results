<?php

namespace App\Request;

use App\Entity\Job;
use App\Entity\Reference;

class ListEventsRequest
{
    public function __construct(
        public readonly ?Job $job,
        public readonly ?Reference $reference,
        public readonly ?string $type,
        public readonly bool $hasReferenceFilter,
    ) {}
}
