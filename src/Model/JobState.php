<?php

namespace App\Model;

use App\Enum\JobState as State;

class JobState
{
    /**
     * @param null|non-empty-string $endState
     */
    public function __construct(
        public readonly State $state,
        public readonly ?string $endState = null,
    ) {
    }

    /**
     * @return array{state: non-empty-string, end_state?: non-empty-string}
     */
    public function toArray(): array
    {
        $data = [
            'state' => $this->state->value,
        ];

        if (State::ENDED === $this->state && is_string($this->endState) && '' !== $this->endState) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }
}
