<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Repository\TokenRepository;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use SmartAssert\SymfonyTestClient\ClientInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApplicationTest extends WebTestCase
{
    protected KernelBrowser $kernelBrowser;
    protected Client $applicationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $factory = self::getContainer()->get(ClientFactory::class);
        \assert($factory instanceof ClientFactory);

        $this->applicationClient = $factory->create($this->getClientAdapter());

        $tokenRepository = self::getContainer()->get(TokenRepository::class);
        if ($tokenRepository instanceof TokenRepository) {
            foreach ($tokenRepository->findAll() as $entity) {
                $tokenRepository->remove($entity);
            }
        }

        $eventRepository = self::getContainer()->get(EventRepository::class);
        if ($eventRepository instanceof EventRepository) {
            foreach ($eventRepository->findAll() as $entity) {
                $eventRepository->remove($entity);
            }
        }
    }

    abstract protected function getClientAdapter(): ClientInterface;
}
