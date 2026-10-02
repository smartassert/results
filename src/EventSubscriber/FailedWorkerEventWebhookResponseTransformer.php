<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Request\AddEvent\InvalidRequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

readonly class FailedWorkerEventWebhookResponseTransformer implements EventSubscriberInterface
{
    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => [
                ['setResponseForRejectWebhookException', 0],
                ['setResponseForInvalidAddRequestException', 0],
            ],
        ];
    }

    public function setResponseForRejectWebhookException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof RejectWebhookException) {
            return;
        }

        $response = $this->createErrorResponse(
            $throwable->getStatusCode(),
            'unparseable_event',
            [
                'message' => $throwable->getMessage(),
            ],
        );

        $event->setResponse($response);
    }

    public function setResponseForInvalidAddRequestException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof InvalidRequestException) {
            return;
        }

        $response = $this->createErrorResponse(
            400,
            'invalid_request',
            [
                $throwable->field => [
                    'value' => null,
                    'message' => $throwable->getMessage(),
                ],
            ],
        );

        $event->setResponse($response);
    }

    /**
     * @param array<mixed> $payload
     */
    private function createErrorResponse(int $statusCode, string $type, array $payload): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => [
                    'type' => $type,
                    'payload' => $payload,
                ],
            ],
            $statusCode
        );
    }
}
