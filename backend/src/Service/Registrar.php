<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use Doctrine\ORM\EntityManagerInterface;

class Registrar
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function register(User $user, Application $application): void
    {
        $this->em->wrapInTransaction(function () use ($user, $application): void {
            // New applications must be reviewed first.
            $application->setStatus(ApplicationStatus::Pending);

            $this->em->persist($user);

            if ($application->getResume() !== null) {
                $this->em->persist($application->getResume());
            }

            $this->em->persist($application);
        });
    }
}
