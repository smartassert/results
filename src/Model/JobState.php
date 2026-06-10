<?php

namespace App\Model;

use App\Enum\JobState as State;

class JobState implements \JsonSerializable
{
    /**
     * @var non-empty-string
     */
    private string $endState;

    public function __construct(
        private readonly State $state,
    ) {}

    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @param non-empty-string $endState
     */
    public function setEndState(string $endState): void
    {
        $this->endState = $endState;
    }

    /**
     * @return ?non-empty-string
     */
    public function getEndState(): ?string
    {
        return $this->endState ?? null;
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
        $hasEnded = State::ENDED === $this->state && isset($this->endState);
        $hasSucceed = $hasEnded && 'complete' === $this->endState;

        $data = [
            'state' => $this->state->value,
            'meta_state' => [
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
        ];

        if (State::ENDED === $this->state && isset($this->endState)) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }
}
