<?php

namespace App\Model;

use App\Enum\JobState as State;

/**
 * @phpstan-type SerializedJob array{
 *   label: non-empty-string,
 *   event_add_url: string,
 *   state: non-empty-string,
 *   has_events: bool,
 *   end_state?: non-empty-string,
 *   meta_state: array{
 *     pending: bool,
 *     ended: bool,
 *     succeeded: bool
 *   },
 *   previous_states: value-of<State>[]
 *  }
 */
interface SerializableJobInterface extends \JsonSerializable
{
    /**
     * @return SerializedJob
     */
    public function jsonSerialize(): array;

    /**
     * @return SerializedJob
     */
    public function toArray(): array;
}
