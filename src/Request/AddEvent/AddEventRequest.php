<?php

namespace App\Request\AddEvent;

class AddEventRequest
{
    public const string KEY_JOB = 'job';
    public const string  KEY_SEQUENCE_NUMBER = 'sequence_number';
    public const string  KEY_TYPE = 'type';
    public const string  KEY_LABEL = 'label';
    public const string  KEY_REFERENCE = 'reference';
    public const string  KEY_RELATED_REFERENCES = 'related_references';
    public const string  KEY_BODY = 'body';

    /**
     * @param null|non-empty-string $job
     * @param null|positive-int     $sequenceNumber
     * @param null|non-empty-string $type
     * @param null|non-empty-string $label
     * @param null|non-empty-string $reference
     * @param null|array<mixed>     $relatedReferences
     * @param null|array<mixed>     $body
     */
    public function __construct(
        public readonly ?string $job,
        public readonly ?int $sequenceNumber,
        public readonly ?string $type,
        public readonly ?string $label,
        public readonly ?string $reference,
        public readonly ?array $relatedReferences,
        public readonly ?array $body,
    ) {}
}
