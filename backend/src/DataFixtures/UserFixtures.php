<?php

namespace App\DataFixtures;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $userRepository = $manager->getRepository(User::class);

        $people = [
            ['Ava Nguyen', 'ava@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Liam Chen', 'liam@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Mateo Rivera', 'mateo@demo.com', null, 'Hardware', 'Pending', ['ROLE_USER']],
            ['Jordan Lee', 'jordan@demo.com', null, 'Hardware', 'Pending', ['ROLE_USER']],
            ['Sofia Patel', 'sofia@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Noah Brooks', 'noah@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Eva Flores', 'eva@demo.com', null, 'Hardware', 'Pending', ['ROLE_USER']],
            ['Alex Kim', 'alex@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Judge One', 'judge1@demo.com', null, 'Software', 'Pending', ['ROLE_JUDGE']],
            ['Judge Two', 'judge2@demo.com', null, 'Hardware', 'Pending', ['ROLE_JUDGE']],
            ['Judge Three', 'judge3@demo.com', null, 'Software', 'Pending', ['ROLE_JUDGE']],
        ];

        foreach ($people as [$name, $email, $team, $track, $state, $roles]) {
            $user = $userRepository->findOneBy(['email' => $email]) ?? new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setTeam($team);
            $user->setTrack($track);
            $user->setState($state);
            $user->setRoles($roles);

            if (!$user->getPassword()) {
                // Set a password so login works for newly seeded rows.
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, 'password')
                );
            }

            if ($user->getId() === null) {
                $manager->persist($user);
            }
        }

        for ($i = 1; $i <= TeamFixtures::TEST_TEAM_COUNT; $i++) {
            $number = sprintf('%02d', $i);
            $track = $i % 2 === 0 ? 'Hardware' : 'Software';
            /** @var Team $team */
            $team = $this->getReference(TeamFixtures::testTeamReference($i), Team::class);

            $leaderDemoEmail = sprintf('zz.team.%s.lead@demo.com', $number);
            $leaderLegacyEmail = sprintf('zz.team.%s.lead@example.test', $number);
            $leaderCandidates = [];
            $leaderDemo = $userRepository->findOneBy(['email' => $leaderDemoEmail]);
            if ($leaderDemo !== null) {
                $leaderCandidates[] = $leaderDemo;
            }
            $leaderLegacy = $userRepository->findOneBy(['email' => $leaderLegacyEmail]);
            if ($leaderLegacy !== null) {
                $leaderCandidates[] = $leaderLegacy;
            }
            if ($leaderCandidates === []) {
                $newLeader = new User();
                $newLeader->setEmail($leaderDemoEmail);
                $manager->persist($newLeader);
                $leaderCandidates[] = $newLeader;
            }
            foreach ($leaderCandidates as $leader) {
                $leader->setName(sprintf('ZZ Team %s Lead', $number));
                $leader->setTeam($team);
                $leader->setTrack($track);
                $leader->setState('Pending');
                $leader->setRoles(['ROLE_TEAM_LEADER']);
                if (!$leader->getPassword()) {
                    $leader->setPassword($this->passwordHasher->hashPassword($leader, 'password'));
                }
            }

            $memberDemoEmail = sprintf('zz.team.%s.member@demo.com', $number);
            $memberLegacyEmail = sprintf('zz.team.%s.member@example.test', $number);
            $memberCandidates = [];
            $memberDemo = $userRepository->findOneBy(['email' => $memberDemoEmail]);
            if ($memberDemo !== null) {
                $memberCandidates[] = $memberDemo;
            }
            $memberLegacy = $userRepository->findOneBy(['email' => $memberLegacyEmail]);
            if ($memberLegacy !== null) {
                $memberCandidates[] = $memberLegacy;
            }
            if ($memberCandidates === []) {
                $newMember = new User();
                $newMember->setEmail($memberDemoEmail);
                $manager->persist($newMember);
                $memberCandidates[] = $newMember;
            }
            foreach ($memberCandidates as $member) {
                $member->setName(sprintf('ZZ Team %s Member', $number));
                $member->setTeam($team);
                $member->setTrack($track);
                $member->setState('Pending');
                $member->setRoles(['ROLE_USER']);
                if (!$member->getPassword()) {
                    $member->setPassword($this->passwordHasher->hashPassword($member, 'password'));
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TeamFixtures::class,
        ];
    }
}
