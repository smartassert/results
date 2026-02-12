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
    ) {}

    /**
     * @return array{
     *     'state': non-empty-string,
     *     'end_state'?: non-empty-string,
     *     'meta_state': array{'ended': bool, 'succeeded': bool}
     * }
     */
    public function toArray(): array
    {
        $hasEnded = State::ENDED === $this->state && is_string($this->endState);
        $hasSucceed = $hasEnded && 'complete' === $this->endState;

        $data = [
            'state' => $this->state->value,
            'meta_state' => [
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
        ];

        if (State::ENDED === $this->state && is_string($this->endState)) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }
}
