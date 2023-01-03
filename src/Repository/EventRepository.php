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
     * @param non-empty-string $typeScope
     *
     * @return Event[]
     */
    public function findByTypeScope(Job $job, string $typeScope): array
    {
        $queryBuilder = $this->createQueryBuilder('Event');
        $queryBuilder
            ->select()
            ->where('Event.job = :JobLabel')
            ->andWhere('Event.type LIKE :EventType')
            ->setParameter('JobLabel', $job->label)
            ->setParameter('EventType', $typeScope . '%')
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
