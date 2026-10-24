<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WaitingRoomController extends AbstractController
{
    #[Route('/waiting-room', name: 'app_waiting_room')]
    public function index(): Response
    {
        return $this->render('waiting_room/waiting.html.twig', [
            'target_date' => '2027-02-20T00:00:00', //
        ]);
    }
}
