<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class WaitingRoomController extends AbstractController
{
    #[Route('/waiting-room', name: 'app_waiting_room')]
    public function index(#[Autowire(env: 'REGISTRATION_OPEN_DATE')] string $registrationOpenDate)
    {
        return $this->render('waiting_room/waiting.html.twig', [
            'target_date' => $registrationOpenDate,
        ]);
    }
}
