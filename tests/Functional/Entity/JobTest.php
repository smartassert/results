<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Job;
use App\ObjectFactory\UlidFactory;
use App\Repository\JobRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class JobTest extends WebTestCase
{
    private JobRepository $repository;
    private EntityManagerInterface $entityManager;
    private UlidFactory $ulidFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = self::getContainer()->get(JobRepository::class);
        \assert($repository instanceof JobRepository);
        $this->repository = $repository;

        $this->ulidFactory = new UlidFactory();

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

        $entity = new Job($this->ulidFactory->create(), $jobLabel, $userId);

        $this->repository->add($entity);

        self::assertSame(1, $this->repository->count([]));

        self::assertSame($jobLabel, $entity->getLabel());
        self::assertSame($userId, ObjectReflector::getProperty($entity, 'userId'));

        $this->entityManager->clear();

        $retrievedEntity = $this->repository->findOneBy(['token' => $entity->getToken()]);

        self::assertNotSame($entity, $retrievedEntity);
        self::assertEquals($entity, $retrievedEntity);
    }

    public function testJobLabelIsUnique(): void
    {
        self::assertSame(0, $this->repository->count([]));

        $jobLabel = md5((string) rand());
        $userId = md5((string) rand());

        $this->entityManager->persist(new Job($this->ulidFactory->create(), $jobLabel, $userId));
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->entityManager->persist(new Job($this->ulidFactory->create(), $jobLabel, $userId));

        self::expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }
}
