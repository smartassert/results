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
    ) {
    }

    public function makeCreateJobRequest(
        ?string $authenticationToken,
        string $label,
        string $method = 'POST'
    ): ResponseInterface {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('job_create', ['label' => $label]),
            $this->createAuthorizationHeader($authenticationToken)
        );
    }

    /**
     * @param array<string, array<mixed>|string> $payload
     */
    public function makeAddEventRequest(string $jobToken, array $payload, string $method = 'POST'): ResponseInterface
    {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('event_add', ['token' => $jobToken]),
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode($payload)
        );
    }

    public function makeListEventRequest(
        ?string $authenticationToken,
        string $label,
        string $reference,
        string $method = 'GET'
    ): ResponseInterface {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('event_list', ['label' => $label, 'reference' => $reference]),
            $this->createAuthorizationHeader($authenticationToken)
        );
    }

    public function makeStatusRequest(): ResponseInterface
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
