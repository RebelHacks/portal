<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Service\Me;

// Until HACKER_PAGES_OPEN is on, a hacker's portal is the lobby and nothing else.
// Every other page sends them back to it. Organizers and judges are not affected.
class ClosedPagesSubscriber implements EventSubscriberInterface
{
    // A new page is shut to hackers unless it's added here.
    private const OPEN_ROUTES = [
        'app_home',
        'app_lobby',
        'app_login',
        'app_login_link',
        'app_login_link_check',
        'app_logout',
        'app_password_new',
        'app_register',
        'app_register_sent',
        'app_register_verify',
        'app_register_finish',
        'app_terms',
    ];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urls,
        #[Autowire('%app.hacker_pages_open%')]
        private bool $open,
        private Me $me,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // The default priority runs after the firewall, so the logged-in user is known.
        return [KernelEvents::REQUEST => 'onRequest'];
    }


    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (\in_array($route, self::OPEN_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (
            !$user instanceof User
            || $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_JUDGE')
        ) {
            return;
        }

        $approved = $this->me->application()?->getStatus()
            === ApplicationStatus::Accepted;

        if (!$this->open || !$approved) {
            $event->setResponse(
                new RedirectResponse(
                    $this->urls->generate('app_lobby')
                )
            );
        }
    }
}
