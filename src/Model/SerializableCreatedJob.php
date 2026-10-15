<?php

namespace App\Model;

use App\Enum\JobState as State;

readonly class SerializableCreatedJob extends SerializableJob implements SerializableJobInterface
{
    /**
     * @param non-empty-string $label
     * @param non-empty-string $token
     */
    public function __construct(
        string $label,
        State $state,
        private string $eventAddUrl,
        private string $token,
        private string $eventNotifyUrl,
    ) {
        parent::__construct($label, $state, false, null);
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'event_add_url' => $this->eventAddUrl,
                'token' => $this->token,

                'event_notify' => [
                    'url' => $this->eventNotifyUrl,
                    'token' => $this->token,
                ],
            ]
        );
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
