<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

// Runs when someone logs in with an emailed link. People ask for one because they forgot their
// password, so the next page lets them set a new one without typing the old one.
class LoginLinkSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    // Session key: the time until which this session may set a password without the old one.
    public const MAY_SET_PASSWORD_UNTIL = 'login_link.may_set_password_until';

    private const WINDOW = '15 minutes';

    public function __construct(
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $request->getSession()->set(
            self::MAY_SET_PASSWORD_UNTIL,
            (new \DateTimeImmutable('+'.self::WINDOW))->getTimestamp(),
        );

        return new RedirectResponse($this->urls->generate('app_password_new'));
    }
}
