<?php

namespace App\Repository;

use App\Entity\Hackathon;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /** @return Team[] */
    public function findAtHackathon(Hackathon $hackathon, int $page = 1, int $perPage = 25): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.hackathon = :h')->setParameter('h', $hackathon)
            ->orderBy('t.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()->getResult();
    }

    public function countAtHackathon(Hackathon $hackathon): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.hackathon = :h')->setParameter('h', $hackathon)
            ->getQuery()->getSingleScalarResult();
    }

    // Teams per track, counted when asked so it's always right. Key null is "no track yet".
    /** @return array<int|null, int> */
    public function countByTrack(Hackathon $hackathon): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.track) AS track, COUNT(t.id) AS n')
            ->andWhere('t.hackathon = :h')->setParameter('h', $hackathon)
            ->groupBy('t.track')
            ->getQuery()->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['track'] === null ? null : (int) $row['track']] = (int) $row['n'];
        }

        return $counts;
    }
}
