<?php

namespace App\Model;

use App\Enum\JobState as State;

readonly class Job implements \JsonSerializable
{
    /**
     * @param non-empty-string      $label
     * @param null|non-empty-string $endState
     */
    public function __construct(
        private string $label,
        private string $eventAddUrl,
        private State $state,
        private ?string $endState = null,
    ) {}

    /**
     * @return array{
     *     label: non-empty-string,
     *     event_add_url: string,
     *     state: non-empty-string,
     *     end_state?: non-empty-string,
     *     meta_state: array{
     *       ended: bool,
     *       succeeded: bool
     *     }
     * }
     */
    public function jsonSerialize(): array
    {
        $hasEnded = State::ENDED === $this->state && is_string($this->endState);
        $hasSucceed = $hasEnded && 'complete' === $this->endState;

        $data = [
            'label' => $this->label,
            'event_add_url' => $this->eventAddUrl,
            'state' => $this->state->value,
            'meta_state' => [
                'ended' => $hasEnded,
                'succeeded' => $hasSucceed,
            ],
        ];

        if ($hasEnded && is_string($this->endState)) {
            $data['end_state'] = $this->endState;
        }

        return $data;
    }
}
