<?php

namespace App\Request;

class CreateJobRequest
{
    public const string KEY_LABEL = 'label';
    public const string KEY_NOTIFY_URL = 'notify_url';

    /**
     * @param ?non-empty-string $label
     * @param ?non-empty-string $notifyUrl
     */
    public function __construct(
        public readonly ?string $label,
        public readonly ?string $notifyUrl,
    ) {}
}
