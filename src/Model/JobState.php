<?php

namespace App\Model;

use App\Enum\JobState as State;

readonly class JobState implements \JsonSerializable
{
    /**
     * @param null|non-empty-string $endState
     */
    public function __construct(
        private State $state,
        private ?string $endState = null,
    ) {}

    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @return ?non-empty-string
     */
    public function getEndState(): ?string
    {
        return $this->endState;
    }

    /**
     * @return array{
     *     'state': non-empty-string,
     *     'end_state'?: non-empty-string,
     *     'meta_state': array{'ended': bool, 'succeeded': bool}
     * }
     */
    public function jsonSerialize(): array
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
