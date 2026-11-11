<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

// The logged-in person's application and team for the current hackathon. Looked up once per request.
class Me implements ResetInterface
{
    private bool $loaded = false;
    private ?Application $application = null;
    private ?TeamMember $membership = null;

    public function __construct(
        private Security $security,
        private CurrentHackathon $currentHackathon,
        private ApplicationRepository $applications,
        private EntityManagerInterface $em,
    ) {
    }

    public function application(): ?Application
    {
        $this->load();

        return $this->application;
    }

    public function team(): ?Team
    {
        $this->load();

        return $this->membership?->getTeam();
    }

    public function leadsTeam(): bool
    {
        $this->load();
        $team = $this->membership?->getTeam();

        return $team !== null && $team->getLeader() === $this->membership;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $user = $this->security->getUser();
        $hackathon = $this->currentHackathon->get();
        if (!$user instanceof User || $hackathon === null) {
            return;
        }

        $this->application = $this->applications->findForUserAtHackathon($user, $hackathon);
        if ($this->application !== null) {
            $this->membership = $this->em->getRepository(TeamMember::class)->findOneBy(['application' => $this->application]);
        }
    }

    public function reset(): void
    {
        $this->loaded = false;
        $this->application = null;
        $this->membership = null;
    }
}
