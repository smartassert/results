<?php

namespace App\Request;

class InvalidAddEventRequestException extends \Exception
{
    public function __construct(
        public readonly string $field,
        string $expectedFormat
    ) {
        $message = sprintf(
            'Required field "%s" invalid, missing from request or not %s.',
            $field,
            $expectedFormat
        );

        parent::__construct($message);
    }
}
