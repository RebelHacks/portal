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
        $user = $this->getUser();

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY') || !($user instanceof User)) {
            return $this->render(
                'bundles/TwigBundle/Exception/error404.html.twig',
                [],
                new Response(status: Response::HTTP_NOT_FOUND),
            );
        }

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
