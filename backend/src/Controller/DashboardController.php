<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// should we organize the components folder better or no?

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {

        # site will be updated to have its own dashboard tab and content - replace activeTab to dashboard when that time comes
        return $this->render('dashboard/index.html.twig' , [
            'activeTab' => 'Team',
        ]);
    }

   // Team dashboard routes - would we prefer different routes or a single route?
   // would we handle team-specific actions through separate routes or within the same route? 
   // current approach is to have separate routes for each tab, but this could be consolidated into a single route with dynamic rendering based on the active tab.
    #[Route('/team', name: 'dashboard_team')]
    public function team(): Response
    {


        return $this->render('dashboard/index.html.twig', [
            'activeTab' => 'Team',
            'teamId' => "testTeamName",
            'isLeader' => false,
        ]);
    }

    #[Route('/schedule', name: 'dashboard_schedule')]
    public function schedule(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'activeTab' => 'Schedule',
        ]);
    }

    #[Route('/submissions', name: 'dashboard_submissions')]
    public function submissions(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'activeTab' => 'Submissions',
        ]);
    }

}