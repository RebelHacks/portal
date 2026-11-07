<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\FileKind;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Service\CurrentEvent;
use App\Service\UploadStorage;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier
    ) {}

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        RateLimiterFactoryInterface $registrationLimiter,
        CurrentEvent $currentEvent,
        UploadStorage $uploads,
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
        }

        // An application belongs to an event, and only while its registration is open.
        $event = $currentEvent->get();
        $now = new \DateTimeImmutable();

        if ($event === null || !$event->isRegistrationOpen($now)) {
            return $this->render('registration/not_open.html.twig', [
                'event' => $event,
                'now' => $now,
            ]);
        }

        $user = new User();

        $form = $this->createForm(
            RegistrationFormType::class,
            $user,
        );

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
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            // A taken email gets the same answer just as slowly (the hash above runs either way),
            // so sign-up can't be used to check who has an account.
            if ($em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]) !== null) {
                return $this->redirectAfterSignUp();
            }

            $application = (new Application())
                ->setUser($user)
                ->setEvent($event)
                ->setSchool($form->get('school')->getData())
                ->setMajor($form->get('major')->getData())
                ->setAttendedBefore($form->get('attendedBefore')->getData())
                // the form can't be submitted without ticking the terms box
                ->setAgreedToTermsAt($now);

            $resume = null;

            // The file first, then the rows. The disk isn't part of the transaction,
            // so if saving the rows fails, the file is deleted.
            try {
                // Organizers dropped resumes, so uploads are off. To turn them back on, uncomment
                // these lines, the resume field in RegistrationFormType and its block in register.html.twig.
                // /** @var UploadedFile $upload */
                // $upload = $form->get('resume')->getData();
                // $resume = $uploads->save($upload, $user, FileKind::Resume);
                // $application->setResume($resume);
                // $em->persist($resume);

                $em->persist($user);
                $em->persist($application);
                $em->flush();
            } catch (\Throwable $exception) {
                if ($resume !== null) {
                    $uploads->delete($resume);
                }

                // the same email signed up at the same moment
                if (!$exception instanceof UniqueConstraintViolationException) {
                    throw $exception;
                }
            }

            // No sign-in here: landing in the lobby would show the email was new.
            return $this->redirectAfterSignUp();
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }

    // TODO: email a verification link, or tell the owner of a taken email they already have an account
    private function redirectAfterSignUp(): Response
    {
        $this->addFlash(
            'success',
            'Registration successful! You can now log in.'
        );

        return $this->redirectToRoute('app_login');
    }
}
