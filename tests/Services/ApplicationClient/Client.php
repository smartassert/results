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

    public function makeCreateTokenRequest(string $jobLabel, string $method = 'POST'): ResponseInterface
    {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('token_create', ['job_label' => $jobLabel])
        );
    }

    /**
     * @param array<string, array<mixed>|string> $payload
     */
    public function makeAddEventRequest(string $token, array $payload, string $method = 'POST'): ResponseInterface
    {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('event_add', ['token' => $token]),
            [
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query($payload)
        );
    }
}
