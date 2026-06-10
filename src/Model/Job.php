<?php

namespace App\Model;

use App\Enum\JobState as State;

class Job implements \JsonSerializable
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

    /**
     * @return array{
     *     label: non-empty-string,
     *     event_add_url: string,
     *     state: non-empty-string,
     *     end_state?: non-empty-string,
     *     meta_state: array{
     *       pending: bool,
     *       ended: bool,
     *       succeeded: bool
     *     }
     * }
     */
    public function jsonSerialize(): array
    {
        $hasEnded = State::ENDED === $this->state && isset($this->endState);
        $hasSucceed = $hasEnded && 'complete' === $this->endState;

        $data = [
            'label' => $this->label,
            'event_add_url' => $this->eventAddUrl,
            'state' => $this->state->value,
            'meta_state' => [
                'pending' => State::AWAITING_EVENTS === $this->state,
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
        ];

        if ($hasEnded) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }
}
