<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface as Response;
use SmartAssert\ResultsClient\Client as ResultsClient;
use SmartAssert\ResultsClient\Model\Event;
use SmartAssert\ResultsClient\Model\EventInterface;
use SmartAssert\ResultsClient\Model\ResourceReference;
use SmartAssert\ResultsClient\Model\ResourceReferenceCollection;
use SmartAssert\ResultsClient\Model\ResourceReferenceInterface;
use SmartAssert\ServiceClient\Exception\UnauthorizedException;
use SmartAssert\SymfonyTestClient\ClientInterface;

readonly class ResultsClientAdapter implements ClientInterface
{
    public function __construct(
        private ResultsClient $resultsClient,
        private HttpResponseFactory $httpResponseFactory,
    ) {}

    public function makeRequest(string $method, string $uri, array $headers = [], ?string $body = null): Response
    {
        if ('POST' === $method && str_starts_with($uri, '/job/')) {
            try {
                $resultsClientResponse = $this->resultsClient->createJob(
                    $this->getAuthenticationTokenFromHeaders($headers),
                    $this->getJobLabelFromUri($uri),
                );
            } catch (UnauthorizedException) {
                return new GuzzleResponse(401);
            }

            return $this->httpResponseFactory->createJobResponse($resultsClientResponse);
        }

        if ('GET' === $method && str_starts_with($uri, '/job/')) {
            try {
                $resultsClientResponse = $this->resultsClient->getJobStatus(
                    $this->getAuthenticationTokenFromHeaders($headers),
                    $this->getJobLabelFromUri($uri),
                );
            } catch (UnauthorizedException) {
                return new GuzzleResponse(401);
            }

            return $this->httpResponseFactory->createJobStatusResponse($resultsClientResponse);
        }

        if ('POST' === $method && str_starts_with($uri, '/event/add/')) {
            $event = $this->createEventFromJsonBody((string) $body);
            \assert($event instanceof EventInterface);

            try {
                $resultsClientResponse = $this->resultsClient->addEvent(
                    $this->getJobLabelFromUri($uri),
                    $event,
                );
            } catch (UnauthorizedException) {
                return new GuzzleResponse(401);
            }

            $bodyData = json_decode((string) $body, true);
            $bodyData = is_array($bodyData) ? $bodyData : [];
            $bodyValue = $bodyData['body'] ?? null;
            $hasBodyValue = null !== $bodyValue;

            return $this->httpResponseFactory->createEventResponse($resultsClientResponse, $hasBodyValue);
        }

        if ('GET' === $method && str_starts_with($uri, '/event/list/')) {
            try {
                $resultsClientResponse = $this->resultsClient->listEvents(
                    $this->getAuthenticationTokenFromHeaders($headers),
                    $this->getJobLabelFromUri($uri),
                    $this->getValueFromUri('reference', $uri),
                    $this->getValueFromUri('type', $uri),
                );
            } catch (UnauthorizedException) {
                return new GuzzleResponse(401);
            }

            return $this->httpResponseFactory->createEventListResponse($resultsClientResponse);
        }

        return new GuzzleResponse(404);
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

    private function createEventFromJsonBody(string $body): ?EventInterface
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }

        $sequenceNumber = $data['sequence_number'] ?? null;
        $sequenceNumber = is_int($sequenceNumber) ? $sequenceNumber : null;
        $sequenceNumber = $sequenceNumber >= 1 ? $sequenceNumber : null;
        if (null === $sequenceNumber) {
            return null;
        }

        $type = $data['type'] ?? null;
        $type = is_string($type) ? $type : null;
        $type = '' !== $type ? $type : null;
        if (null === $type) {
            return null;
        }

        $resourceReference = $this->createResourceReference($data);
        if (null === $resourceReference) {
            return null;
        }

        $body = $data['body'] ?? null;
        $body = is_array($body) ? $body : [];

        $event = new Event($sequenceNumber, $type, $resourceReference, $body);

        $relatedReferences = $data['related_references'] ?? null;
        $relatedReferences = is_array($relatedReferences) ? $relatedReferences : [];

        if ([] !== $relatedReferences) {
            $filteredRelatedReferences = [];

            foreach ($relatedReferences as $relatedReference) {
                if (!is_array($relatedReference)) {
                    continue;
                }

                $relatedResourceReference = $this->createResourceReference($relatedReference);
                if (null === $relatedResourceReference) {
                    continue;
                }

                $filteredRelatedReferences[] = $relatedResourceReference;
            }

            $event = $event->withRelatedReferences(new ResourceReferenceCollection($filteredRelatedReferences));
        }

        return $event;
    }

    /**
     * @param array<mixed> $data
     */
    private function createResourceReference(array $data): ?ResourceReferenceInterface
    {
        $label = $data['label'] ?? null;
        $label = is_string($label) ? $label : null;
        $label = '' !== $label ? $label : null;
        if (null === $label) {
            return null;
        }

        $reference = $data['reference'] ?? null;
        $reference = is_string($reference) ? $reference : null;
        $reference = '' !== $reference ? $reference : null;
        if (null === $reference) {
            return null;
        }

        return new ResourceReference($label, $reference);
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
