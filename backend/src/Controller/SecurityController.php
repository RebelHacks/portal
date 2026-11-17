<?php

namespace App\Controller;

use App\Service\CurrentHackathon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private CurrentHackathon $currentHackathon,
    ) {
    }

    private function isRegistrationOpen(): bool
    {
        $hackathon = $this->currentHackathon->get();

        return $hackathon !== null
            && $hackathon->isRegistrationOpen(
                new \DateTimeImmutable()
            );
    }

    private function isBeforeRegistrationOpens(): bool
    {
        $hackathon = $this->currentHackathon->get();
        $opensAt = $hackathon?->getRegistrationOpensAt();

        return $opensAt === null
            || new \DateTimeImmutable() < $opensAt;
    }

    // Public entrance to the portal.
    #[Route(path: '/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        // Registration is open: direct visitors to sign up.
        if ($this->isRegistrationOpen()) {
            return $this->redirectToRoute('app_register');
        }

        // Registration hasn't opened yet: show waiting room.
        if ($this->isBeforeRegistrationOpens()) {
            return $this->render('waiting_room/index.html.twig');
        }

        // Registration has closed: allow existing users to log in.
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        // Hide login before registration opens.
        if ($this->isBeforeRegistrationOpens()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }
}
