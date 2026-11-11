<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\Track;
use App\Repository\TeamRepository;
use App\Service\CurrentHackathon;
use App\Service\Me;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// A hacker's team pages. Reads are real; writes are stubbed (see the TODOs) until the team work lands.
class TeamController extends AbstractController
{
    public function __construct(
        private CurrentHackathon $currentHackathon,
        private Me $me,
    ) {
    }

    // Your team, or how to get one.
    #[Route('/team', name: 'app_team', methods: ['GET'])]
    public function team(TeamRepository $teams): Response
    {
        $hackathon = $this->currentHackathon->get();
        $team = $this->me->team();

        return $this->render($team ? 'team/team.html.twig' : 'team/none.html.twig', [
            'hackathon' => $hackathon,
            'application' => $this->me->application(),
            'team' => $team,
            'leads' => $this->me->leadsTeam(),
            // TODO: the looking-for-a-team list (applications with looking_for_team = yes and no team), paged
            'looking' => [],
            'openTeams' => $hackathon ? $teams->findAtHackathon($hackathon) : [],
        ]);
    }

    // TODO: creates the team and its leader in one transaction (see the design doc's Teams section).
    #[Route('/team/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->render('team/new.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
        ]);
    }

    // Invites you sent (leader) or received (no team).
    #[Route('/team/invites', name: 'app_team_invites', methods: ['GET'])]
    public function invites(): Response
    {
        return $this->render('team/invites.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'team' => $this->me->team(),
            'leads' => $this->me->leadsTeam(),
            // TODO: TeamInvite rows for this team (leader) or for this application (invitee)
            'invites' => [],
        ]);
    }

    // Every track with its live count. Leaders pick here.
    #[Route('/team/tracks', name: 'app_tracks', methods: ['GET'])]
    public function tracks(TeamRepository $teams, EntityManagerInterface $em): Response
    {
        $hackathon = $this->currentHackathon->get();
        $tracks = $hackathon ? $em->getRepository(Track::class)->findBy(['hackathon' => $hackathon], ['id' => 'ASC']) : [];

        return $this->render('team/tracks.html.twig', [
            'hackathon' => $hackathon,
            'tracks' => $tracks,
            'counts' => $hackathon ? $teams->countByTrack($hackathon) : [],
            'team' => $this->me->team(),
            'leads' => $this->me->leadsTeam(),
        ]);
    }

    // Everyone's teams, with counts per track.
    #[Route('/teams', name: 'app_teams', methods: ['GET'])]
    public function teams(Request $request, TeamRepository $teams, EntityManagerInterface $em): Response
    {
        $hackathon = $this->currentHackathon->get();
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('team/all.html.twig', [
            'hackathon' => $hackathon,
            'teams' => $hackathon ? $teams->findAtHackathon($hackathon, $page) : [],
            'total' => $hackathon ? $teams->countAtHackathon($hackathon) : 0,
            'page' => $page,
            'tracks' => $hackathon ? $em->getRepository(Track::class)->findBy(['hackathon' => $hackathon], ['id' => 'ASC']) : [],
            'counts' => $hackathon ? $teams->countByTrack($hackathon) : [],
            'mine' => $this->me->team(),
        ]);
    }

    // Join a team from its invite link. TODO: the Beginner gate, the size check, the join itself.
    #[Route('/join/{code}', name: 'app_team_join', methods: ['GET', 'POST'])]
    public function join(string $code, TeamRepository $teams): Response
    {
        $team = $teams->findOneBy(['inviteCode' => $code]);
        if ($team === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('team/join.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'team' => $team,
        ]);
    }
}
