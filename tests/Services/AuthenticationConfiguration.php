<?php

declare(strict_types=1);

namespace App\Tests\Services;

use SmartAssert\UsersClient\Client;
use SmartAssert\UsersClient\Model\ApiKey;
use SmartAssert\UsersClient\Model\RefreshableToken;
use SmartAssert\UsersClient\Model\Token;

class AuthenticationConfiguration
{
    private RefreshableToken $frontendToken;
    private ApiKey $apiKey;
    private Token $apiToken;

    public function __construct(
        public readonly string $userEmail,
        public readonly string $userPassword,
        private readonly Client $usersClient,
    ) {
    }

    public function getValidApiToken(): string
    {
        if (!isset($this->apiToken)) {
            $apiToken = $this->usersClient->createApiToken($this->getDefaultApiKey()->key);
            if (null === $apiToken) {
                throw new \RuntimeException('Valid API token is null');
            }

            $this->apiToken = $apiToken;
        }

        return $this->apiToken->token;
    }

    public function getInvalidApiToken(): string
    {
        return 'invalid api token value';
    }

    private function getFrontendToken(): RefreshableToken
    {
        if (!isset($this->frontendToken)) {
            $this->frontendToken = $this->usersClient->createFrontendToken($this->userEmail, $this->userPassword);
        }

        return $this->frontendToken;
    }

    private function getDefaultApiKey(): ApiKey
    {
        if (!isset($this->apiKey)) {
            $apiKeys = $this->usersClient->listUserApiKeys($this->getFrontendToken());
            $apiKey = $apiKeys->getDefault();
            if (null === $apiKey) {
                throw new \RuntimeException('API key is null');
            }

            $this->apiKey = $apiKey;
        }

        return $this->apiKey;
    }
}
