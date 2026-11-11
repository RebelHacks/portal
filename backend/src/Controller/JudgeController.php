<?php

namespace App\Controller;

use App\Entity\Team;
use App\Service\CurrentHackathon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// A judge's pages. The judging tables aren't in the schema yet (see the design doc), so these are
// the layouts with nothing behind them. access_control keeps /judge to ROLE_JUDGE.
#[Route('/judge')]
class JudgeController extends AbstractController
{
    public function __construct(private CurrentHackathon $currentHackathon)
    {
    }

    #[Route('', name: 'app_judge', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('judge/home.html.twig', ['hackathon' => $this->currentHackathon->get()]);
    }

    // TODO: the panel's assignments in table order, from panel_assignment once judging is back.
    #[Route('/teams', name: 'app_judge_teams', methods: ['GET'])]
    public function teams(): Response
    {
        return $this->render('judge/teams.html.twig', ['hackathon' => $this->currentHackathon->get()]);
    }

    // TODO: criteria from the criterion table; saving writes score rows.
    #[Route('/score/{team}', name: 'app_judge_score', methods: ['GET', 'POST'])]
    public function score(Team $team): Response
    {
        return $this->render('judge/score.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'team' => $team,
        ]);
    }

    #[Route('/rubric', name: 'app_judge_rubric', methods: ['GET'])]
    public function rubric(): Response
    {
        return $this->render('judge/rubric.html.twig', ['hackathon' => $this->currentHackathon->get()]);
    }
}
