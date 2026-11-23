<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\SignedUriException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// The signed URL contains the submitted email and names. Signing prevents tampering;
// it does not encrypt the values. Do not put passwords or other secrets in this URL.
class RegistrationLink
{
    public const LIFETIME = '24 hours';

    public function __construct(
        private UriSigner $signer,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function for(string $email, string $firstName, string $lastName): string
    {
        $url = $this->urls->generate('app_register_verify', [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->signer->sign($url, new \DateTimeImmutable('+' . self::LIFETIME));
    }

    /** @return array{email: string, firstName: string, lastName: string}|null */
    public function detailsFrom(Request $request): ?array
    {
        try {
            $this->signer->verify($request);
        } catch (SignedUriException) {
            return null;
        }

        $email = mb_strtolower(trim($request->query->getString('email')));
        $firstName = trim($request->query->getString('firstName'));
        $lastName = trim($request->query->getString('lastName'));

        // An older email-only link won't be enough to finish the new flow.
        if (
            $email === ''
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || !preg_match('/@(?:[a-z0-9-]+\.)+edu$/i', $email)
            || mb_strlen($email) > 180
            || $firstName === '' || $lastName === ''
            || mb_strlen($firstName) > 128 || mb_strlen($lastName) > 128
        ) {
            return null;
        }

        return [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];
    }
}
