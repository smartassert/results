<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $isPartialTypeMatch = str_ends_with($type, self::TYPE_WILDCARD);
        $typeOperator = $isPartialTypeMatch ? 'LIKE' : '=';

        if ($isPartialTypeMatch) {
            $type = str_replace(self::TYPE_WILDCARD, self::QUERY_WILDCARD, $type);
        }

        $queryBuilder = $this->createQueryBuilder('Event');
        $queryBuilder
            ->select()
            ->where('Event.job = :JobLabel')
            ->andWhere('Event.type ' . $typeOperator . ' :EventType')
            ->setParameter('JobLabel', $job->label)
            ->setParameter('EventType', $type)
        ;

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
}
