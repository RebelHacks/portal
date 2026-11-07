<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function findForUserAtEvent(User $user, Event $event): ?Application
    {
        return $this->findOneBy(['user' => $user, 'event' => $event]);
    }
}
