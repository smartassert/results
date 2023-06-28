<?php

namespace App\ObjectFactory;

use App\Entity\Job;
use App\Enum\JobState as State;
use App\Model\JobEndedEvent;
use App\Model\JobState;
use App\Repository\EventRepository;

class JobStateFactory
{
    public function __construct(
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function create(Job $job): JobState
    {
        if (false === $this->eventRepository->hasForJob($job)) {
            return new JobState(State::AWAITING_EVENTS);
        }

        $jobEndedEvents = $this->eventRepository->findByType($job, 'job/ended');

        if ([] !== $jobEndedEvents) {
            $jobEndedEvent = new JobEndedEvent($jobEndedEvents[0]);

            return new JobState(State::ENDED, $jobEndedEvent->getEndState());
        }

        $hasExecutionEndedEvent = $this->eventRepository->hasForType($job, 'job/execution/ended');
        if ($hasExecutionEndedEvent) {
            return new JobState(State::EXECUTED);
        }

        $hasExecutionStartedEvent = $this->eventRepository->hasForType($job, 'job/execution/started');
        if ($hasExecutionStartedEvent) {
            return new JobState(State::EXECUTING);
        }

        $hasCompilationEndedEvent = $this->eventRepository->hasForType($job, 'job/compilation/ended');
        if ($hasCompilationEndedEvent) {
            return new JobState(State::COMPILED);
        }

        $hasCompilationStartedEvent = $this->eventRepository->hasForType($job, 'job/compilation/started');
        if ($hasCompilationStartedEvent) {
            return new JobState(State::COMPILING);
        }

        $hasJobStartedEvent = $this->eventRepository->hasForType($job, 'job/started');

        return new JobState($hasJobStartedEvent ? State::STARTED : State::AWAITING_EVENTS);
    }
}
