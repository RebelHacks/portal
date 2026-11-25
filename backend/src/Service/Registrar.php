<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Repository\ApplicationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

// Saves a new account with its application and decides the spot: accepted while the hackathon
// has room and nobody is waiting, waitlisted otherwise.
class Registrar
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationRepository $applications,
    ) {
    }

    public function register(User $user, Application $application): void
    {
        $this->em->wrapInTransaction(function () use ($user, $application): void {
            // Lock the hackathon's row, so two sign-ups at the same moment can't both take the last spot.
            $hackathon = $application->getHackathon();
            $this->em->refresh($hackathon, LockMode::PESSIMISTIC_WRITE);

            $accepted = $this->applications->count([
                'hackathon' => $hackathon,
                'status' => ApplicationStatus::Accepted,
            ]);
            // When someone drops out, their spot belongs to the first person waiting, not to a new sign-up.
            $waiting = $this->applications->count([
                'hackathon' => $hackathon,
                'status' => ApplicationStatus::Waitlisted,
            ]);

            $application->setStatus(ApplicationStatus::Submitted);

            $this->em->persist($user);
            if ($application->getResume() !== null) {
                $this->em->persist($application->getResume());
            }
            $this->em->persist($application);
        });
    }
}
