<?php

namespace App\Request;

class AddEventRequest
{
    public const KEY_TYPE = 'type';
    public const KEY_REFERENCE = 'reference';
    public const KEY_PAYLOAD = 'payload';

    /**
     * @param array<mixed> $payload
     */
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $reference,
        public readonly ?array $payload,
    ) {
    }
}
