<?php

namespace App\Request\AddEvent;

class ValidatedRequest
{
    /**
     * @param non-empty-string  $job
     * @param positive-int      $sequenceNumber
     * @param non-empty-string  $type
     * @param non-empty-string  $label
     * @param non-empty-string  $reference
     * @param null|array<mixed> $relatedReferences
     * @param null|array<mixed> $body
     */
    public function __construct(
        public readonly string $job,
        public readonly int $sequenceNumber,
        public readonly string $type,
        public readonly string $label,
        public readonly string $reference,
        public readonly ?array $relatedReferences,
        public readonly ?array $body,
    ) {}
}
