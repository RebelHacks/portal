<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\SignedUriException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// The link in the sign-up email. It carries the address and an expiry, signed with APP_SECRET,
// so opening it proves the person can read that inbox.
class RegistrationLink
{
    public const LIFETIME = '24 hours';

    public function __construct(
        private UriSigner $signer,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function for(string $email): string
    {
        $url = $this->urls->generate('app_register_verify', ['email' => $email], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->signer->sign($url, new \DateTimeImmutable('+' . self::LIFETIME));
    }

    // The address the link was made for. Null if the link was changed or has expired.
    public function emailFrom(Request $request): ?string
    {
        try {
            $this->signer->verify($request);
        } catch (SignedUriException) {
            return null;
        }

        return $request->query->getString('email') ?: null;
    }
}
