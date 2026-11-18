<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\ResultsClient\Client as ResultsClient;
use SmartAssert\ServiceClient\Exception\UnauthorizedException;
use SmartAssert\SymfonyTestClient\ClientInterface;

readonly class ResultsClientAdapter implements ClientInterface
{
    public function __construct(
        private ResultsClient $resultsClient,
        private HttpResponseFactory $httpResponseFactory,
    ) {}

    public function makeRequest(
        string $method,
        string $uri,
        array $headers = [],
        ?string $body = null
    ): ResponseInterface {
        try {
            if ('POST' === $method && str_starts_with($uri, '/job/')) {
                $decodedBody = [];
                if (is_string($body)) {
                    parse_str($body, $decodedBody);
                }

                $notifyUrl = $decodedBody['notify_url'] ?? null;
                $notifyUrl = is_string($notifyUrl) ? $notifyUrl : null;
                $notifyUrl = '' !== $notifyUrl ? $notifyUrl : null;

                return $this->httpResponseFactory->createJobResponse(
                    $this->resultsClient->createJob(
                        $this->getAuthenticationTokenFromHeaders($headers),
                        $this->getJobLabelFromUri($uri),
                        $notifyUrl,
                    )
                );
            }

            if ('GET' === $method && str_starts_with($uri, '/job/')) {
                return $this->httpResponseFactory->createJobResponse(
                    $this->resultsClient->getJobStatus(
                        $this->getAuthenticationTokenFromHeaders($headers),
                        $this->getJobLabelFromUri($uri),
                    )
                );
            }

            if ('GET' === $method && str_starts_with($uri, '/event/list/')) {
                return $this->httpResponseFactory->createEventListResponse(
                    $this->resultsClient->listEvents(
                        $this->getAuthenticationTokenFromHeaders($headers),
                        $this->getJobLabelFromUri($uri),
                        $this->getValueFromUri('reference', $uri),
                        $this->getValueFromUri('type', $uri),
                    )
                );
            }
        } catch (UnauthorizedException) {
            return new Response(401);
        }

        return new Response(404);
    }

    /**
     * @param array<mixed> $headers
     *
     * @return non-empty-string
     */
    private function getAuthenticationTokenFromHeaders(array $headers): string
    {
        $authorizationHeader = $headers['authorization'] ?? null;
        if (!is_string($authorizationHeader)) {
            return 'missing-token';
        }

        $token = str_replace('Bearer ', '', $authorizationHeader);

        return '' === $token ? 'missing-token' : $token;
    }

    /**
     * @return non-empty-string
     */
    private function getJobLabelFromUri(string $uri): string
    {
        if (str_starts_with($uri, '/job/') || str_starts_with($uri, '/event/add/')) {
            $label = str_replace(['/job/', '/event/add/'], '', $uri);

            return '' === $label ? 'missing-label' : $label;
        }

        if (str_starts_with($uri, '/event/list/')) {
            $label = str_replace('/event/list/', '', $uri);

            $queryPosition = strpos($label, '?');
            if (false !== $queryPosition) {
                $label = substr($label, 0, $queryPosition);
            }

            return '' === $label ? 'missing-label' : $label;
        }

        return 'missing-label';
    }

    /**
     * @return ?non-empty-string
     */
    private function getValueFromUri(string $key, string $uri): ?string
    {
        $urlParts = parse_url($uri);
        $query = $urlParts['query'] ?? '';

        $queryParts = [];
        parse_str($query, $queryParts);

        $value = $queryParts[$key] ?? null;
        $value = is_string($value) ? $value : null;

        return '' === $value ? null : $value;
    }
}
