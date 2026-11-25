<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

// Saves a new account with its application and decides the spot: accepted while the hackathon
// has room and nobody is waiting, waitlisted otherwise.
class Registrar
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {
    }

    public function register(User $user, Application $application): void
    {
        $this->em->wrapInTransaction(function () use ($user, $application): void {
            // Lock the hackathon's row, so two sign-ups at the same moment can't both take the last spot.
            $hackathon = $application->getHackathon();
            $this->em->refresh($hackathon, LockMode::PESSIMISTIC_WRITE);

            $application->setStatus(ApplicationStatus::Submitted);

            $email = new TemplatedEmail()
                ->to($user->getEmail())
                ->subject(sprintf('Thank you for applying to %s', $hackathon->getName()))
                ->htmlTemplate('emails/application_confirmation.html.twig')
                ->textTemplate('emails/application_confirmation.txt.twig')
                ->context([
                    'hackathon' => $hackathon
                ]);

            $this->mailer->send($email);

            $this->em->persist($user);
            if ($application->getResume() !== null) {
                $this->em->persist($application->getResume());
            }
            $this->em->persist($application);
        });
    }
}
