<?php

namespace App\Model;

use App\Enum\JobState as State;

readonly class SerializableJob implements SerializableJobInterface
{
    /**
     * @param non-empty-string  $label
     * @param ?non-empty-string $endState
     */
    public function __construct(
        public string $label,
        public State $state,
        public bool $hasEvents,
        public ?string $endState,
    ) {}

    public function jsonSerialize(): array
    {
        $hasEnded = State::ENDED === $this->state && isset($this->endState);
        $hasSucceed = $hasEnded && 'complete' === $this->endState;

        $previousStates = [];
        foreach ($this->state->getPreviousStates() as $previousState) {
            $previousStates[] = $previousState->value;
        }

        $data = [
            'label' => $this->label,
            'state' => $this->state->value,
            'has_events' => $this->hasEvents,
            'meta_state' => [
                'pending' => State::AWAITING_EVENTS === $this->state,
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
            'previous_states' => $previousStates,
        ];

        if (null !== $this->endState) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
