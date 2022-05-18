<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Token;
use App\Repository\TokenRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TokenTest extends WebTestCase
{
    private TokenRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(TokenRepository::class);
        \assert($repository instanceof TokenRepository);
        $this->repository = $repository;

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
    }

    public function testCreate(): void
    {
        self::assertSame(0, $this->repository->count([]));

        $jobLabel = md5((string) rand());
        $userId = md5((string) rand());

        $token = new Token($jobLabel, $userId);

        $this->repository->add($token);

        self::assertSame(1, $this->repository->count([]));

        self::assertSame($jobLabel, $token->getJobLabel());
        self::assertSame($userId, $token->getUserId());
    }

    public function testJobLabelIsUnique(): void
    {
        self::assertSame(0, $this->repository->count([]));

        $jobLabel = md5((string) rand());
        $userId = md5((string) rand());

        $token = new Token($jobLabel, $userId);

        $this->entityManager->persist($token);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->entityManager->persist($token);

        self::expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
