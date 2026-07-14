<?php

declare(strict_types=1);

namespace App\Enum;

enum JobState: string
{
    case AWAITING_EVENTS = 'awaiting-events';
    case STARTED = 'started';
    case COMPILING = 'compiling';
    case COMPILED = 'compiled';
    case EXECUTING = 'executing';
    case EXECUTED = 'executed';
    case ENDED = 'ended';

    /**
     * @return JobState[]
     */
    public function getPreviousStates(): array
    {
        if (JobState::STARTED === $this) {
            return [
                JobState::AWAITING_EVENTS,
            ];
        }

        if (JobState::COMPILING === $this) {
            return [
                JobState::AWAITING_EVENTS,
                JobState::STARTED,
            ];
        }

        if (JobState::COMPILED === $this) {
            return [
                JobState::AWAITING_EVENTS,
                JobState::STARTED,
                JobState::COMPILING,
            ];
        }

        if (JobState::EXECUTING === $this) {
            return [
                JobState::AWAITING_EVENTS,
                JobState::STARTED,
                JobState::COMPILING,
                JobState::COMPILED,
            ];
        }

        if (JobState::EXECUTED === $this) {
            return [
                JobState::AWAITING_EVENTS,
                JobState::STARTED,
                JobState::COMPILING,
                JobState::COMPILED,
                JobState::EXECUTING,
            ];
        }

        if (JobState::ENDED === $this) {
            return [
                JobState::AWAITING_EVENTS,
                JobState::STARTED,
                JobState::COMPILING,
                JobState::COMPILED,
                JobState::EXECUTING,
                JobState::EXECUTED,
            ];
        }

        return [];
    }
}
