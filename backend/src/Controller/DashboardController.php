<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {

        # site will be updated to have its own dashboard tab and content - replace activeTab to dashboard when that time comes
        return $this->render('dashboard/index.html.twig' , [
            'activeTab' => 'Team',
            'teamId' => null,
        ]);
    }


    #[Route('/team', name: 'dashboard_team')]
    public function team(): Response
    {

        # some parameters passed here as proof of concept
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