<?php

namespace App\Request;

class AddEventRequest
{
    public const KEY_HEADER_SECTION = 'header';
    public const KEY_SEQUENCE_NUMBER = 'sequence_number';
    public const KEY_TYPE = 'type';
    public const KEY_LABEL = 'label';
    public const KEY_REFERENCE = 'reference';
    public const KEY_BODY = 'body';

    /**
     * @param null|positive-int     $sequenceNumber
     * @param null|non-empty-string $type
     * @param null|non-empty-string $label
     * @param null|non-empty-string $reference
     * @param null|array<mixed>     $body
     */
    public function __construct(
        public readonly ?int $sequenceNumber,
        public readonly ?string $type,
        public readonly ?string $label,
        public readonly ?string $reference,
        public readonly ?array $body,
    ) {
    }
}
