<?php

namespace App\Model;

use App\Enum\JobState as State;

class JobState implements \JsonSerializable
{
    /**
     * @param null|non-empty-string $endsState
     */
    public function __construct(
        public readonly State $state,
        public readonly ?string $endsState = null,
    ) {
    }

    /**
     * @return array{state: non-empty-string, end_state?: non-empty-string}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'state' => $this->state->value,
        ];

        if (State::ENDED === $this->state && is_string($this->endsState) && '' !== $this->endsState) {
            $data['end_state'] = $this->endsState;
        }

        return $data;
    }
}
