<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Repository\EventRepository;
use App\Repository\JobRepository;
use App\Repository\ReferenceRepository;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use SmartAssert\SymfonyTestClient\ClientInterface;
use SmartAssert\TestAuthenticationProviderBundle\ApiTokenProvider;
use SmartAssert\TestAuthenticationProviderBundle\UserProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApplicationTest extends WebTestCase
{
    protected static KernelBrowser $kernelBrowser;
    protected Client $applicationClient;
    protected static UserProvider $users;
    protected static ApiTokenProvider $apiTokens;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$kernelBrowser = self::createClient();

        $users = self::getContainer()->get(UserProvider::class);
        \assert($users instanceof UserProvider);
        self::$users = $users;

        $apiTokens = self::getContainer()->get(ApiTokenProvider::class);
        \assert($apiTokens instanceof ApiTokenProvider);
        self::$apiTokens = $apiTokens;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::getContainer()->get(ClientFactory::class);
        \assert($factory instanceof ClientFactory);

        $this->applicationClient = $factory->create($this->getClientAdapter());

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);

        $jobRepository = self::getContainer()->get(JobRepository::class);
        if ($jobRepository instanceof JobRepository) {
            foreach ($jobRepository->findAll() as $entity) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }

        $eventRepository = self::getContainer()->get(EventRepository::class);
        if ($eventRepository instanceof EventRepository) {
            foreach ($eventRepository->findAll() as $entity) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }

        $referenceRepository = self::getContainer()->get(ReferenceRepository::class);
        if ($referenceRepository instanceof ReferenceRepository) {
            foreach ($referenceRepository->findAll() as $entity) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }
    }

    abstract protected function getClientAdapter(): ClientInterface;
}
