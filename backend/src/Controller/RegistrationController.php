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
use App\Service\UploadStorage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use League\Flysystem\FilesystemException;
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

// Sign-up in two steps. Step 1 takes an email address and sends it a signed link. Step 2, reached
// only from that link, takes everything else and creates the account. Nothing is saved before
// step 2, so there are no unverified accounts.
class RegistrationController extends AbstractController
{
    // The address a link proved. Step 2 reads the address from here, never from the form.
    private const VERIFIED_EMAIL = 'registration.verified_email';
    // The address step 1 just emailed, shown back on the "check your inbox" page.
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
            $email = mb_strtolower($form->get('email')->getData());

            try {
                // Past the limit for one address nothing is sent, and the page says the same thing.
                if ($registrationEmailLimiter->create($email)->consume()->isAccepted()) {
                    // A taken address gets an email too, and the same page. Sign-up can't be used
                    // to check who has an account.
                    $mailer->send(
                        $this->users->findOneBy(['email' => $email]) === null
                            ? $this->linkEmail($email, $link)
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

    // The link in the email. Opening it saves nothing, so a mail scanner that follows links does no harm.
    #[Route('/register/verify', name: 'app_register_verify', methods: ['GET'])]
    public function verify(Request $request, RegistrationLink $link): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        $email = $link->emailFrom($request);

        if ($email === null) {
            $this->addFlash('error', 'That link has expired or isn\'t complete. Enter your email for a new one.');

            return $this->redirectToRoute('app_register');
        }

        // Remember the address and go to a clean URL, so the link doesn't stay in the address bar or history.
        $request->getSession()->set(self::VERIFIED_EMAIL, $email);

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
        $email = $session->get(self::VERIFIED_EMAIL);

        if (!\is_string($email)) {
            return $this->redirectToRoute('app_register');
        }
        if ($notOpen = $this->notOpen()) {
            return $notOpen;
        }
        // A link makes one account. Using it again ends here.
        if ($this->users->findOneBy(['email' => $email]) !== null) {
            return $this->alreadyRegistered($request);
        }

        $user = (new User())
            ->setEmail($email)
            ->setIsVerified(true);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            $application = (new Application())
                ->setUser($user)
                ->setHackathon($this->currentHackathon->get())
                ->setSchool($form->get('school')->getData())
                ->setMajor($form->get('major')->getData())
                ->setAttendedBefore($form->get('attendedBefore')->getData())
                // the form can't be submitted without ticking the terms box
                ->setAgreedToTermsAt(new \DateTimeImmutable())
                ->setTermsVersion(TermsController::VERSION);

            // The file first, then the rows. Storage isn't part of the transaction,
            // so if saving the rows fails, the file is deleted.
            try {
                /** @var UploadedFile $upload */
                $upload = $form->get('resume')->getData();
                $resume = $uploads->save($upload, $user, FileKind::Resume);
            } catch (FilesystemException $exception) {
                $logger->error('Resume upload failed at sign-up.', ['exception' => $exception]);
                $form->get('resume')->addError(new FormError(
                    'We couldn\'t save your resume. Please try again in a few minutes.'
                ));

                return $this->render('registration/finish.html.twig', [
                    'registrationForm' => $form,
                    'email' => $email,
                ]);
            }

            $application->setResume($resume);

            try {
                $registrar->register($user, $application);
            } catch (\Throwable $exception) {
                $uploads->delete($resume);

                // the same link finished in two tabs at the same moment
                if (!$exception instanceof UniqueConstraintViolationException) {
                    throw $exception;
                }

                return $this->alreadyRegistered($request);
            }

            $session->remove(self::VERIFIED_EMAIL);
            $session->remove(self::SENT_TO);

            // The link proved the inbox is theirs and they just chose the password, so sign them in.
            return $security->login($user, 'form_login', 'main') ?? $this->redirectToRoute('app_lobby');
        }

        return $this->render('registration/finish.html.twig', [
            'registrationForm' => $form,
            'email' => $email,
        ]);
    }

    // An application belongs to a hackathon, and only while its registration is open.
    // Before it opens, the front door has the waiting room. After it closes, this says so.
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
        $request->getSession()->remove(self::VERIFIED_EMAIL);
        $this->addFlash('success', 'You already have an account. Log in.');

        return $this->redirectToRoute('app_login');
    }

    private function linkEmail(string $email, RegistrationLink $link): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to($email)
            ->subject('Finish registering for RebelHacks')
            ->htmlTemplate('emails/registration_link.html.twig')
            ->textTemplate('emails/registration_link.txt.twig')
            ->context([
                'url' => $link->for($email),
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
