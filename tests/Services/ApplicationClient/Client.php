<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\ClientInterface;
use Symfony\Component\Routing\RouterInterface;

class Client
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RouterInterface $router,
    ) {}

    public function makeJobCreationRequest(
        ?string $authenticationToken,
        string $label,
        ?string $notifyUrl
    ): ResponseInterface {
        $payload = [];
        if (is_string($notifyUrl)) {
            $payload['notify_url'] = $notifyUrl;
        }

        return $this->makeJobRequest($authenticationToken, $label, 'POST', $payload);
    }

    public function makeJobRetrievalRequest(?string $authenticationToken, string $label): ResponseInterface
    {
        return $this->makeJobRequest($authenticationToken, $label, 'GET');
    }

    /**
     * @param array<string, string> $payload
     */
    public function makeJobRequest(
        ?string $authenticationToken,
        string $label,
        string $method,
        array $payload = []
    ): ResponseInterface {
        $hasPayload = [] !== $payload;

        $headers = $this->createAuthorizationHeader($authenticationToken);
        if ($hasPayload) {
            $headers['content-type'] = 'application/x-www-form-urlencoded';
        }

        $body = null;
        if ($hasPayload) {
            $body = http_build_query($payload);
        }

        return $this->client->makeRequest(
            $method,
            $this->router->generate('job_create', ['label' => $label]),
            $headers,
            $body,
        );
    }

    /**
     * @param array<mixed> $payload
     */
    public function makeEventAddRequest(string $eventAddUrl, array $payload, string $method = 'POST'): ResponseInterface
    {
        return $this->client->makeRequest(
            $method,
            $eventAddUrl,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($payload)
        );
    }

    public function makeEventListRequest(
        ?string $authenticationToken,
        string $label,
        ?string $reference,
        ?string $type = null,
        string $method = 'GET'
    ): ResponseInterface {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('event_list', ['label' => $label, 'reference' => $reference, 'type' => $type]),
            $this->createAuthorizationHeader($authenticationToken)
        );
    }

    public function makeServiceStatusRequest(): ResponseInterface
    {
        return $this->client->makeRequest('GET', $this->router->generate('status'));
    }

    public function makeHealthCheckRequest(string $method = 'GET'): ResponseInterface
    {
        return $this->client->makeRequest($method, $this->router->generate('health-check'));
    }

    /**
     * @return array<string, string>
     */
    private function createAuthorizationHeader(?string $authenticationToken): array
    {
        $headers = [];
        if (is_string($authenticationToken)) {
            $headers = [
                'authorization' => 'Bearer ' . $authenticationToken,
            ];
        }

        return $headers;
    }
}
