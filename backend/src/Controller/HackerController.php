<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Repository\ScheduleItemRepository;
use App\Service\CurrentHackathon;
use App\Service\Me;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The rest of a hacker's pages: schedule, map, submission, settings.
class HackerController extends AbstractController
{
    public function __construct(
        private CurrentHackathon $currentHackathon,
        private Me $me,
    ) {
    }

    #[Route('/schedule', name: 'app_schedule', methods: ['GET'])]
    public function schedule(ScheduleItemRepository $items): Response
    {
        $hackathon = $this->currentHackathon->get();

        return $this->render('hacker/schedule.html.twig', [
            'hackathon' => $hackathon,
            'items' => $hackathon ? $items->findAtHackathon($hackathon) : [],
            'now' => new \DateTimeImmutable(),
        ]);
    }

    // TODO: Jason's traced floor plan replaces the placeholder drawing; pins come from team tables and panels.
    #[Route('/map', name: 'app_map', methods: ['GET'])]
    public function map(): Response
    {
        return $this->render('hacker/map.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
        ]);
    }

    // The team's write-up. Anyone on the team edits it. TODO: the save, with updated_by and updated_at.
    #[Route('/submission', name: 'app_submission', methods: ['GET', 'POST'])]
    public function submission(Request $request, EntityManagerInterface $em): Response
    {
        $team = $this->me->team();
        $submission = $team ? $em->getRepository(Submission::class)->findOneBy(['team' => $team]) : null;

        return $this->render('hacker/submission.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'team' => $team,
            'submission' => $submission,
        ]);
    }

    // TODO: each form here saves: profile, password (re-hash), discoverability, resume replace.
    #[Route('/settings', name: 'app_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        return $this->render('hacker/settings.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'application' => $this->me->application(),
        ]);
    }
}
