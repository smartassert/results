<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\JobInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class NotifiableJobStateChangedEvent extends Event implements NotifiableEventInterface
{
    public const string REMOTE_EVENT_NAME = 'results.job.state_changed';

    /**
     * @param array<mixed> $payload
     */
    public function __construct(
        private readonly JobInterface $job,
        private readonly array $payload,
    ) {}

    public function getNotifyUrl(): ?string
    {
        $baseUrl = $this->job->getNotifyUrl();
        if (null === $baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/' . $this->getRemoteEventName();
    }

    public function getRemoteEventName(): string
    {
        return self::REMOTE_EVENT_NAME;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
