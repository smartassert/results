<?php

namespace App\ObjectFactory;

use App\Enum\JobState as State;
use App\Model\JobEndedEvent;
use App\Model\JobState;
use App\Repository\EventRepository;

class JobStateFactory
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {}

    public function create(string $jobLabel): JobState
    {
        if (false === $this->eventRepository->hasForJob($jobLabel)) {
            return new JobState(State::AWAITING_EVENTS);
        }

        $jobEndedEvents = $this->eventRepository->findByType($jobLabel, 'job/ended');

        if ([] !== $jobEndedEvents) {
            $jobEndedEvent = new JobEndedEvent($jobEndedEvents[0]);

            $jobState = new JobState(State::ENDED);
            $jobState->setEndState($jobEndedEvent->getEndState());

            return $jobState;
        }

        $hasExecutionEndedEvent = $this->eventRepository->hasForType($jobLabel, 'lifecycle/execution-completed');
        if ($hasExecutionEndedEvent) {
            return new JobState(State::EXECUTED);
        }

        $hasExecutionStartedEvent = $this->eventRepository->hasForType($jobLabel, 'lifecycle/execution-started');
        if ($hasExecutionStartedEvent) {
            return new JobState(State::EXECUTING);
        }

        $hasCompilationEndedEvent = $this->eventRepository->hasForType($jobLabel, 'lifecycle/compilation-completed');
        if ($hasCompilationEndedEvent) {
            return new JobState(State::COMPILED);
        }

        $hasCompilationStartedEvent = $this->eventRepository->hasForType($jobLabel, 'lifecycle/compilation-started');
        if ($hasCompilationStartedEvent) {
            return new JobState(State::COMPILING);
        }

        $hasJobStartedEvent = $this->eventRepository->hasForType($jobLabel, 'job/started');

        return new JobState($hasJobStartedEvent ? State::STARTED : State::AWAITING_EVENTS);
    }
}
