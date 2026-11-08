<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LobbyController extends AbstractController
{
    #[Route('/lobby', name: 'app_lobby', methods: ['GET'])]
    public function index(): Response
    {
        // Visitors without a session never get here: access_control sends them to log in.
        /** @var User $user */
        $user = $this->getUser();

        $name = trim(sprintf(
            '%s %s',
            $user->getFirstName() ?? '',
            $user->getLastName() ?? '',
        ));
        $major = trim((string) $user->getMajor());

        return $this->render('lobby/index.html.twig', [
            'name' => $name,
            'major' => $major !== '' ? $major : 'Not provided',
        ]);
    }
}
