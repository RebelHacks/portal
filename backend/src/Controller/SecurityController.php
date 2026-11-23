<?php

namespace App\Controller;

use App\Form\LoginLinkType;
use App\Repository\UserRepository;
use App\Service\CurrentHackathon;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class SecurityController extends AbstractController
{
    // matches login_link's lifetime in security.yaml
    private const LOGIN_LINK_MINUTES = 15;

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
            return $this->render('waiting_room/index.html.twig', [
                'hackathon' => $this->currentHackathon->get(),
            ]);
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

    // "Forgot your password?" leads here. The emailed link logs the person in without one.
    #[Route(path: '/login/link', name: 'app_login_link', methods: ['GET', 'POST'])]
    public function loginLink(
        Request $request,
        UserRepository $users,
        LoginLinkHandlerInterface $loginLinks,
        MailerInterface $mailer,
        RateLimiterFactoryInterface $loginLinkLimiter,
        RateLimiterFactoryInterface $loginLinkEmailLimiter,
        LoggerInterface $logger,
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }
        if ($this->isBeforeRegistrationOpens()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(LoginLinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$loginLinkLimiter->create($request->getClientIp())->consume()->isAccepted()) {
            $form->addError(new FormError('Too many requests from your network. Please try again later.'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower($form->get('email')->getData());
            $user = $users->findOneBy(['email' => $email]);

            try {
                // No account, or past the limit for one address: nothing is sent, and the page says the same thing.
                if ($user !== null && $loginLinkEmailLimiter->create($email)->consume()->isAccepted()) {
                    $link = $loginLinks->createLoginLink($user);
                    $mailer->send(
                        (new TemplatedEmail())
                            ->to($email)
                            ->subject('Your RebelHacks login link')
                            ->htmlTemplate('emails/login_link.html.twig')
                            ->textTemplate('emails/login_link.txt.twig')
                            ->context([
                                'url' => $link->getUrl(),
                                'minutes' => self::LOGIN_LINK_MINUTES,
                            ])
                    );
                }
            } catch (TransportExceptionInterface $exception) {
                $logger->error('Login link email failed to send.', ['exception' => $exception]);
                $form->addError(new FormError('We couldn\'t send the email. Please try again in a few minutes.'));

                return $this->render('security/login_link.html.twig', ['form' => $form]);
            }

            $this->addFlash('success', sprintf(
                'If %s has an account, a login link is on its way. It works once, for %d minutes.',
                $email,
                self::LOGIN_LINK_MINUTES,
            ));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/login_link.html.twig', ['form' => $form]);
    }

    // Where the emailed link lands. This page only shows a Log in button; the firewall's login_link
    // handles the button's POST (see security.yaml).
    #[Route(path: '/login/link/check', name: 'app_login_link_check', methods: ['GET', 'POST'])]
    public function loginLinkCheck(Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        $user = $request->query->getString('user');
        $expires = $request->query->getString('expires');
        $hash = $request->query->getString('hash');

        if ($user === '' || $hash === '' || (int) $expires < time()) {
            $this->addFlash('error', 'That link has expired or isn\'t complete. Enter your email for a new one.');

            return $this->redirectToRoute('app_login_link');
        }

        return $this->render('security/login_link_check.html.twig', [
            'user' => $user,
            'expires' => $expires,
            'hash' => $hash,
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
