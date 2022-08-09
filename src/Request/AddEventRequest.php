<?php

namespace App\Request;

class AddEventRequest
{
    public const KEY_SEQUENCE_NUMBER = 'sequence_number';
    public const KEY_TYPE = 'type';
    public const KEY_LABEL = 'label';
    public const KEY_REFERENCE = 'reference';
    public const KEY_PAYLOAD = 'payload';

    /**
     * @param positive-int|null $sequenceNumber
     * @param non-empty-string|null $type
     *
     * @param array<mixed> $payload
     */
    public function __construct(
        public readonly ?int $sequenceNumber,
        public readonly ?string $type,
        public readonly ?string $label,
        public readonly ?string $reference,
        public readonly ?array $payload,
    ) {
    }
}
