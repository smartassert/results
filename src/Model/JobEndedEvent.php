<?php

namespace App\Model;

use App\Entity\Event;

class JobEndedEvent
{
    private const END_STATE_UNKNOWN = 'unknown';

    public function __construct(
        private readonly Event $event,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getEndState(): string
    {
        $bodyData = $this->event->getBody();
        $endState = self::END_STATE_UNKNOWN;

        if (is_array($bodyData)) {
            $endState = $bodyData['end_state'] ?? self::END_STATE_UNKNOWN;
            $endState = is_string($endState) ? $endState : self::END_STATE_UNKNOWN;
            $endState = '' !== $endState ? $endState : self::END_STATE_UNKNOWN;
        }

        return $endState;
    }
}
