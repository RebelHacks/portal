<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\FileKind;
use App\Form\RegistrationEmailType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\CurrentHackathon;
use App\Service\Registrar;
use App\Service\RegistrationLink;
use App\Service\UploadFailed;
use App\Service\UploadStorage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

// Step 1 collects names and email, then sends a signed link. Step 2 creates
// the verified account and application. No unverified user row is stored.
class RegistrationController extends AbstractController
{
    private const VERIFIED_DETAILS = 'registration.verified_details';
    private const SENT_TO = 'registration.sent_to';

    public function __construct(
        private CurrentHackathon $currentHackathon,
        private UserRepository $users,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        RegistrationLink $link,
        MailerInterface $mailer,
        RateLimiterFactoryInterface $registrationLimiter,
        RateLimiterFactoryInterface $registrationEmailLimiter,
        LoggerInterface $logger,
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }
        if ($notOpen = $this->notOpen()) {
            return $notOpen;
        }

        $form = $this->createForm(RegistrationEmailType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && !$registrationLimiter->create($request->getClientIp())->consume()->isAccepted()
        ) {
            $form->addError(new FormError(
                'Too many sign-ups from your network. Please try again later.'
            ));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $firstName = trim((string) $form->get('firstName')->getData());
            $lastName = trim((string) $form->get('lastName')->getData());

            try {
                // Keep the same response for an existing account and for rate-limited addresses.
                if ($registrationEmailLimiter->create($email)->consume()->isAccepted()) {
                    $mailer->send(
                        $this->users->findOneBy(['email' => $email]) === null
                            ? $this->linkEmail($email, $firstName, $lastName, $link)
                            : $this->alreadyRegisteredEmail($email)
                    );
                }
            } catch (TransportExceptionInterface $exception) {
                $logger->error('Sign-up email failed to send.', ['exception' => $exception]);
                $form->addError(new FormError(
                    'We couldn\'t send the email. Please try again in a few minutes.'
                ));

                return $this->render('registration/email.html.twig', ['form' => $form]);
            }

            $request->getSession()->set(self::SENT_TO, $email);

            return $this->redirectToRoute('app_register_sent');
        }

        return $this->render('registration/email.html.twig', ['form' => $form]);
    }

    #[Route('/register/sent', name: 'app_register_sent', methods: ['GET'])]
    public function sent(Request $request): Response
    {
        $email = $request->getSession()->get(self::SENT_TO);

        if (!\is_string($email)) {
            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/sent.html.twig', ['email' => $email]);
    }

    // The mail link does not create an account. It only verifies the signature
    // and copies the data into this browser's session before redirecting.
    #[Route('/register/verify', name: 'app_register_verify', methods: ['GET'])]
    public function verify(Request $request, RegistrationLink $link): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        $details = $link->detailsFrom($request);

        if ($details === null) {
            $this->addFlash('error', 'That link has expired or isn\'t complete. Please start registration again.');

            return $this->redirectToRoute('app_register');
        }

        $request->getSession()->set(self::VERIFIED_DETAILS, $details);

        // Remove the signed query parameters from the browser address bar.
        return $this->redirectToRoute('app_register_finish');
    }

    #[Route('/register/finish', name: 'app_register_finish', methods: ['GET', 'POST'])]
    public function finish(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Registrar $registrar,
        Security $security,
        UploadStorage $uploads,
        LoggerInterface $logger,
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        $session = $request->getSession();
        $details = $session->get(self::VERIFIED_DETAILS);

        if (
            !\is_array($details)
            || !\is_string($details['email'] ?? null)
            || !\is_string($details['firstName'] ?? null)
            || !\is_string($details['lastName'] ?? null)
        ) {
            $session->remove(self::VERIFIED_DETAILS);

            return $this->redirectToRoute('app_register');
        }

        $email = $details['email'];
        $firstName = $details['firstName'];
        $lastName = $details['lastName'];

        if ($notOpen = $this->notOpen()) {
            return $notOpen;
        }
        if ($this->users->findOneBy(['email' => $email]) !== null) {
            return $this->alreadyRegistered($request);
        }

        // These values cannot be overwritten by the second form's POST body.
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setIsVerified(true);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // A custom major requires a value even if JavaScript is disabled.
        if ($form->isSubmitted()) {
            if (
                $form->get('major')->getData() === 'Other'
                && trim((string) $form->get('otherMajor')->getData()) === ''
            ) {
                $form->get('otherMajor')->addError(new FormError('Please enter your major.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $school = trim((string) $form->get('school')->getData());
            $major = $form->get('major')->getData() === 'Other'
                ? trim((string) $form->get('otherMajor')->getData())
                : $form->get('major')->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            $application = (new Application())
                ->setUser($user)
                ->setHackathon($this->currentHackathon->get())
                ->setSchool($school)
                ->setMajor($major)
                ->setAttendedBefore($form->get('attendedBefore')->getData())
                ->setAgreedToTermsAt(new \DateTimeImmutable())
                ->setTermsVersion(TermsController::VERSION);

            try {
                /** @var UploadedFile $upload */
                $upload = $form->get('resume')->getData();
                $resume = $uploads->save($upload, $user, FileKind::Resume);
            } catch (UploadFailed $exception) {
                $logger->error('Resume upload failed at sign-up.', ['exception' => $exception]);
                $form->get('resume')->addError(new FormError(
                    'We couldn\'t save your resume. Please try again in a few minutes.'
                ));

                return $this->render('registration/finish.html.twig', [
                    'registrationForm' => $form,
                    'email' => $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                ]);
            }

            $application->setResume($resume);

            try {
                $registrar->register($user, $application);
            } catch (\Throwable $exception) {
                $uploads->delete($resume);

                if (!$exception instanceof UniqueConstraintViolationException) {
                    throw $exception;
                }

                return $this->alreadyRegistered($request);
            }

            $session->remove(self::VERIFIED_DETAILS);
            $session->remove(self::SENT_TO);

            return $security->login($user, 'form_login', 'main') ?? $this->redirectToRoute('app_lobby');
        }

        return $this->render('registration/finish.html.twig', [
            'registrationForm' => $form,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ]);
    }

    private function notOpen(): ?Response
    {
        $hackathon = $this->currentHackathon->get();
        $now = new \DateTimeImmutable();

        if ($hackathon === null || $now < $hackathon->getRegistrationOpensAt()) {
            return $this->redirectToRoute('app_home');
        }
        if ($hackathon->isRegistrationOpen($now)) {
            return null;
        }

        return $this->render('registration/closed.html.twig', ['hackathon' => $hackathon]);
    }

    private function alreadyRegistered(Request $request): Response
    {
        $request->getSession()->remove(self::VERIFIED_DETAILS);
        $this->addFlash('success', 'You already have an account. Log in.');

        return $this->redirectToRoute('app_login');
    }

    private function linkEmail(
        string $email,
        string $firstName,
        string $lastName,
        RegistrationLink $link,
    ): TemplatedEmail {
        return (new TemplatedEmail())
            ->to($email)
            ->subject('Finish registering for RebelHacks')
            ->htmlTemplate('emails/registration_link.html.twig')
            ->textTemplate('emails/registration_link.txt.twig')
            ->context([
                'url' => $link->for($email, $firstName, $lastName),
                'lifetime' => RegistrationLink::LIFETIME,
                'hackathon' => $this->currentHackathon->get(),
            ]);
    }

    private function alreadyRegisteredEmail(string $email): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($email)
            ->subject('You already have a RebelHacks account')
            ->htmlTemplate('emails/already_registered.html.twig')
            ->textTemplate('emails/already_registered.txt.twig');
    }
}
