<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\FileKind;
use App\Form\ChangePasswordType;
use App\Form\LookingForTeamType;
use App\Form\ProfileType;
use App\Form\RegistrationFormType;
use App\Form\ReplaceResumeType;
use App\Service\CurrentHackathon;
use App\Service\Me;
use App\Service\UploadFailed;
use App\Service\UploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

// A person's own details, password and resume. Each panel is its own form,
// so a POST carries only the one that was sent.
class SettingsController extends AbstractController
{
    public function __construct(
        private CurrentHackathon $currentHackathon,
        private Me $me,
        private EntityManagerInterface $em,
        private UploadStorage $uploads,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/settings', name: 'app_settings', methods: ['GET', 'POST'])]
    public function settings(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        RateLimiterFactoryInterface $passwordChangeLimiter,
        #[Autowire('%app.hacker_pages_open%')]
        bool $hackerPagesOpen,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $application = $this->me->application();

        $profile = $this->createForm(ProfileType::class, $this->profileOf($user, $application), [
            'with_application' => $application !== null,
        ]);
        $password = $this->createForm(ChangePasswordType::class);
        // An organizer or judge may have no application, so no resume and no team to find.
        $resume = $application ? $this->createForm(ReplaceResumeType::class) : null;
        // The team switch waits for the team pages (HACKER_PAGES_OPEN).
        $team = $application && $hackerPagesOpen
            ? $this->createForm(LookingForTeamType::class, ['lookingForTeam' => (bool) $application->isLookingForTeam()])
            : null;

        foreach ([$profile, $password, $resume, $team] as $form) {
            $form?->handleRequest($request);
        }

        if ($profile->isSubmitted() && $profile->isValid()) {
            $this->saveProfile($profile->getData(), $user, $application);

            return $this->saved('Your profile is saved.');
        }

        if ($password->isSubmitted()) {
            if (!$passwordChangeLimiter->create((string) $user->getId())->consume()->isAccepted()) {
                // A fresh form, so the page stops saying whether each guess at the current password was right.
                $password = $this->createForm(ChangePasswordType::class);
                $password->addError(new FormError('Too many tries. Please wait a few minutes.'));
            } elseif ($password->isValid()) {
                $user->setPassword($passwordHasher->hashPassword($user, $password->get('plainPassword')->getData()));
                $this->em->flush();
                // A changed password ends every session of this account, this one included. Log back in here.
                $security->login($user, 'form_login', 'main');

                return $this->saved('Your password is changed.');
            }
        }

        if ($resume?->isSubmitted() && $resume->isValid()) {
            try {
                $this->replaceResume($resume->get('resume')->getData(), $user, $application);

                return $this->saved('Your new resume is saved.');
            } catch (UploadFailed $exception) {
                $this->logger->error('Resume upload failed in Settings.', ['exception' => $exception]);
                $resume->get('resume')->addError(new FormError(
                    'We couldn\'t save your resume. Please try again in a few minutes.'
                ));
            }
        }

        if ($team?->isSubmitted() && $team->isValid()) {
            $application->setLookingForTeam($team->get('lookingForTeam')->getData());
            $this->em->flush();

            return $this->saved($application->isLookingForTeam() ? 'Team leaders can find you now.' : 'You\'re off the looking-for-a-team list.');
        }

        return $this->render('hacker/settings.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'application' => $application,
            'profile' => $profile,
            'password' => $password,
            'resume' => $resume,
            'team' => $team,
        ]);
    }

    // Back to the page with a GET, so a refresh doesn't send the form again.
    private function saved(string $message): Response
    {
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_settings');
    }

    /** @return array<string, ?string> what the profile form starts with */
    private function profileOf(User $user, ?Application $application): array
    {
        $profile = ['firstName' => $user->getFirstName(), 'lastName' => $user->getLastName()];

        if ($application !== null) {
            $major = (string) $application->getMajor();
            // A major that isn't on the list was typed in under "Other".
            $listed = $major !== RegistrationFormType::OTHER_MAJOR && \in_array($major, RegistrationFormType::MAJORS, true);
            $profile += [
                'school' => $application->getSchool(),
                'major' => $listed ? $major : RegistrationFormType::OTHER_MAJOR,
                'otherMajor' => $listed ? null : $major,
            ];
        }

        return $profile;
    }

    /** @param array<string, ?string> $profile */
    private function saveProfile(array $profile, User $user, ?Application $application): void
    {
        $user
            ->setFirstName(trim($profile['firstName']))
            ->setLastName(trim($profile['lastName']));

        $application
            ?->setSchool(trim($profile['school']))
            ->setMajor($profile['major'] === RegistrationFormType::OTHER_MAJOR ? trim($profile['otherMajor']) : $profile['major']);

        $this->em->flush();
    }

    /** @throws UploadFailed */
    private function replaceResume(UploadedFile $upload, User $user, Application $application): void
    {
        $old = $application->getResume();
        // The new file first, then the rows, as at sign-up. Storage isn't part of the transaction.
        $new = $this->uploads->save($upload, $user, FileKind::Resume);

        $this->em->persist($new);
        $application->setResume($new);
        if ($old !== null) {
            // Load the old row now. Once it's deleted, its stored name can't be read to delete the file.
            $this->em->initializeObject($old);
            $this->em->remove($old);
        }

        try {
            $this->em->flush();
        } catch (\Throwable $exception) {
            $this->uploads->delete($new);

            throw $exception;
        }

        if ($old !== null) {
            try {
                $this->uploads->delete($old);
            } catch (\Throwable $exception) {
                // The new resume is saved, so this isn't the person's problem. app:sweep-files removes the old file later.
                $this->logger->warning('A replaced resume was not deleted from storage.', ['exception' => $exception]);
            }
        }
    }
}
