<?php

namespace App\DataFixtures;

use App\Controller\TermsController;
use App\Entity\Application;
use App\Entity\Hackathon;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Hackers with applications, two per test team, and three judges. Every password is "password".
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEST_TEAM_COUNT = 25;

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $hackathon = $this->getReference(HackathonFixtures::HACKATHON, Hackathon::class);

        // hash once: the same password for everyone, and hashing 61 times takes a minute
        $hash = $this->passwordHasher->hashPassword(new User(), 'password');

        $hackers = [
            ['Ava', 'Nguyen', 'ava@demo.com', 'Computer Science'],
            ['Liam', 'Chen', 'liam@demo.com', 'Computer Engineering'],
            ['Mateo', 'Rivera', 'mateo@demo.com', 'Electrical Engineering'],
            ['Jordan', 'Lee', 'jordan@demo.com', 'Mechanical Engineering'],
            ['Sofia', 'Patel', 'sofia@demo.com', 'Computer Science'],
            ['Noah', 'Brooks', 'noah@demo.com', 'Information Systems'],
            ['Eva', 'Flores', 'eva@demo.com', 'Mathematics'],
            ['Alex', 'Kim', 'alex@demo.com', 'Computer Science'],
        ];

        foreach ($hackers as $i => [$firstName, $lastName, $email, $major]) {
            $user = $this->user($manager, $firstName, $lastName, $email, $hash);
            $this->application($manager, $user, $hackathon, $major, $i % 2 === 0);
        }

        for ($i = 1; $i <= self::TEST_TEAM_COUNT; $i++) {
            $number = sprintf('%02d', $i);

            foreach (['lead' => 'Lead', 'member' => 'Member'] as $kind => $lastName) {
                $user = $this->user($manager, sprintf('ZZ Team %s', $number), $lastName, sprintf('zz.team.%s.%s@demo.com', $number, $kind), $hash);
                $application = $this->application($manager, $user, $hackathon, 'Computer Science', $i % 3 === 0);
                $this->addReference(self::applicationReference($i, $kind), $application);
            }
        }

        foreach (['One', 'Two', 'Three'] as $i => $lastName) {
            $judge = $this->user($manager, 'Judge', $lastName, sprintf('judge%d@demo.com', $i + 1), $hash);
            $judge->setRoles(['ROLE_JUDGE']);
        }

        $manager->flush();
    }

    public static function applicationReference(int $team, string $kind): string
    {
        return sprintf('application_team_%02d_%s', $team, $kind);
    }

    public function getDependencies(): array
    {
        return [HackathonFixtures::class];
    }

    private function user(ObjectManager $manager, string $firstName, string $lastName, string $email, string $hash): User
    {
        $user = (new User())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPassword($hash)
            ->setIsVerified(true);
        $manager->persist($user);

        return $user;
    }

    private function application(ObjectManager $manager, User $user, Hackathon $hackathon, string $major, bool $attendedBefore): Application
    {
        $application = (new Application())
            ->setUser($user)
            ->setHackathon($hackathon)
            ->setSchool('University of Nevada, Las Vegas')
            ->setMajor($major)
            ->setAttendedBefore($attendedBefore)
            ->setAgreedToTermsAt(new \DateTimeImmutable())
            ->setTermsVersion(TermsController::VERSION)
            ->setShareResumeWithSponsors(true);
        $manager->persist($application);

        return $application;
    }
}
