<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Token;
use App\Repository\JobRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class TokenTest extends WebTestCase
{
    private JobRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(JobRepository::class);
        \assert($repository instanceof JobRepository);
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

        self::assertSame($jobLabel, $token->jobLabel);
        self::assertSame($userId, ObjectReflector::getProperty($token, 'userId'));

        $this->entityManager->clear();

        $retrievedToken = $this->repository->findOneBy(['token' => $token->token]);

        self::assertNotSame($token, $retrievedToken);
        self::assertEquals($token, $retrievedToken);
    }

    public function testJobLabelIsUnique(): void
    {
        self::assertSame(0, $this->repository->count([]));

        $jobLabel = md5((string) rand());
        $userId = md5((string) rand());

        $this->entityManager->persist(new Token($jobLabel, $userId));
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->entityManager->persist(new Token($jobLabel, $userId));

        self::expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
