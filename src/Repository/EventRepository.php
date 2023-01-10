<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method null|Event find($id, $lockMode = null, $lockVersion = null)
 * @method null|Event findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public const TYPE_WILDCARD = '*';
    private const QUERY_WILDCARD = '%';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function add(Event $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Event[]
     */
    public function findByType(Job $job, string $type): array
    {
        $queryBuilder = $this->createJobQueryBuilder($job);
        $queryBuilder = $this->addQueryBuilderTypeConstraint($queryBuilder, $type);

        $query = $queryBuilder->getQuery();

        $result = $query->getResult();
        $result = is_array($result) ? $result : [];

        $filteredResults = [];
        foreach ($result as $entity) {
            if ($entity instanceof Event) {
                $filteredResults[] = $entity;
            }
        }

        return $filteredResults;
    }

    public function hasForType(Job $job, string $type): bool
    {
        $queryBuilder = $this->createJobQueryBuilder($job);
        $queryBuilder = $this->addQueryBuilderTypeConstraint($queryBuilder, $type);
        $queryBuilder = $this->addQueryBuilderCountConstraint($queryBuilder);

        return $this->resultHasIntegerScalarGreaterThanZero($queryBuilder->getQuery());
    }

    public function hasForJob(Job $job): bool
    {
        $queryBuilder = $this->createJobQueryBuilder($job);
        $queryBuilder = $this->addQueryBuilderCountConstraint($queryBuilder);

        return $this->resultHasIntegerScalarGreaterThanZero($queryBuilder->getQuery());
    }

    private function resultHasIntegerScalarGreaterThanZero(Query $query): bool
    {
        try {
            $result = $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return false;
        }

        return 0 !== $result;
    }

    private function addQueryBuilderTypeConstraint(QueryBuilder $queryBuilder, string $type): QueryBuilder
    {
        $isPartialTypeMatch = str_ends_with($type, self::TYPE_WILDCARD);
        $typeOperator = $isPartialTypeMatch ? 'LIKE' : '=';

        if ($isPartialTypeMatch) {
            $type = str_replace(self::TYPE_WILDCARD, self::QUERY_WILDCARD, $type);
        }

        $queryBuilder
            ->andWhere('Event.type ' . $typeOperator . ' :EventType')
            ->setParameter('EventType', $type)
        ;

        return $queryBuilder;
    }

    private function addQueryBuilderCountConstraint(QueryBuilder $queryBuilder): QueryBuilder
    {
        $queryBuilder
            ->select('count(Event.id)')
            ->setMaxResults(1)
        ;

        return $queryBuilder;
    }

    private function createJobQueryBuilder(Job $job): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('Event');
        $queryBuilder
            ->where('Event.job = :JobLabel')
            ->setParameter('JobLabel', $job->label)
        ;

        return $queryBuilder;
    }
}
