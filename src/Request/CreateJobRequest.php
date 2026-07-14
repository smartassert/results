<?php

namespace App\Request;

class CreateJobRequest
{
    public const KEY_LABEL = 'label';

    /**
     * @param ?non-empty-string $label
     */
    public function __construct(
        public readonly ?string $label,
    ) {}
}
