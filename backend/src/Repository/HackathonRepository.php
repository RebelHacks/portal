<?php

namespace App\Repository;

use App\Entity\Hackathon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hackathon>
 */
class HackathonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hackathon::class);
    }

    // The current hackathon is the one that starts last.
    public function findCurrent(): ?Hackathon
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.startsAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
