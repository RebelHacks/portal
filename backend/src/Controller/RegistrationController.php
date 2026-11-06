<?php

namespace App\Controller;

use App\Entity\File as UserFile;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
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
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_lobby');
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
            $password = $form->get('plainPassword')->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $password)
            );

            $user->setRoles(['ROLE_USER']);
            $user->setState('Pending');

            // A taken email gets the same answer just as slowly (the hash above runs either way),
            // so sign-up can't be used to check who has an account.
            if ($em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]) !== null) {
                return $this->redirectAfterSignUp();
            }

            /** @var UploadedFile|null $resume */
            $resume = $form->get('resume')->getData();
            $savedResumePath = null;

            try {
                $em->persist($user);

                if ($resume instanceof UploadedFile) {
                    $uploadDir = $this->getParameter('kernel.project_dir')
                        . '/var/uploads/resumes'; 

                    if (
                        !is_dir($uploadDir)
                        && !mkdir($uploadDir, 0700, true)
                        && !is_dir($uploadDir)
                    ) {
                        throw new \RuntimeException(
                            'Could not create resume directory.'
                        );
                    }

                    $fileName = bin2hex(random_bytes(16)) . '.pdf';

                    $resume->move($uploadDir, $fileName);
                    $savedResumePath = $uploadDir . '/' . $fileName;

                    $fileEntity = new UserFile();
                    $fileEntity->setUser($user);
                    $fileEntity->setType('resume');
                    $fileEntity->setFilepath(
                        'var/uploads/resumes/' . $fileName
                    );

                    $em->persist($fileEntity);
            }

            $em->flush();

        } catch (\Throwable $exception) {
            // Clean up the uploaded file if database saving fails.
            if (
                $savedResumePath !== null
                && is_file($savedResumePath)
            ) {
                unlink($savedResumePath);
            }

            // the same email signed up at the same moment
            if (!$exception instanceof UniqueConstraintViolationException) {
                throw $exception;
            }
        }

        // No sign-in here: landing in the lobby would show the email was new.
        return $this->redirectAfterSignUp();
    }

    return $this->render(
        'registration/register.html.twig',
        [
            'registrationForm' => $form->createView(),
        ]
    );}


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
