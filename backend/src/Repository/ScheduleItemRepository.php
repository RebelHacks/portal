<?php

namespace App\Repository;

use App\Entity\Hackathon;
use App\Entity\ScheduleItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleItem>
 */
class ScheduleItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleItem::class);
    }

    /** @return ScheduleItem[] in time order */
    public function findAtHackathon(Hackathon $hackathon): array
    {
        return $this->findBy(['hackathon' => $hackathon], ['startsAt' => 'ASC']);
    }

    // The first item that hasn't started yet. Null once the weekend's over.
    public function findNext(Hackathon $hackathon, \DateTimeImmutable $now): ?ScheduleItem
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.hackathon = :h')->setParameter('h', $hackathon)
            ->andWhere('s.startsAt > :now')->setParameter('now', $now)
            ->orderBy('s.startsAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
