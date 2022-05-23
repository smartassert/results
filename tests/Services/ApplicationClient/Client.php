<?php

declare(strict_types=1);

namespace App\Tests\Services\ApplicationClient;

use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\ClientInterface;
use Symfony\Component\Routing\RouterInterface;

class Client
{
    public function __construct(
        private ClientInterface $client,
        private RouterInterface $router,
    ) {
    }

    public function makeCreateTokenRequest(string $jobLabel, string $method = 'POST'): ResponseInterface
    {
        return $this->client->makeRequest(
            $method,
            $this->router->generate('token_create', ['job_label' => $jobLabel])
        );
    }
}
