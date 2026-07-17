<?php

namespace App\Model;

use App\Enum\JobState as State;

class SerializableJob implements SerializableJobInterface
{
    /**
     * @var non-empty-string
     */
    private string $endState;

    /**
     * @param non-empty-string $label
     */
    public function __construct(
        private readonly string $label,
        private readonly string $eventAddUrl,
        private readonly State $state,
        private readonly bool $hasEvents,
    ) {}

    /**
     * @param non-empty-string $endState
     */
    public function withEndState(string $endState): self
    {
        $new = clone $this;
        $new->endState = $endState;

        return $new;
    }

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
            'event_add_url' => $this->eventAddUrl,
            'state' => $this->state->value,
            'has_events' => $this->hasEvents,
            'meta_state' => [
                'pending' => State::AWAITING_EVENTS === $this->state,
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
            'previous_states' => $previousStates,
        ];

        if ($hasEnded) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
