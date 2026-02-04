<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $people = [
            ['Ava Nguyen', 'ava@demo.com', 'Neon Ninjas', 'Software', 'Pending', ['ROLE_USER']],
            ['Liam Chen', 'liam@demo.com', 'Neon Ninjas', 'Software', 'Checked In', ['ROLE_USER']],
            ['Mateo Rivera', 'mateo@demo.com', 'Circuit Cowboys', 'Hardware', 'Checked In', ['ROLE_USER']],
            ['Jordan Lee', 'jordan@demo.com', 'Circuit Cowboys', 'Hardware', 'Pending', ['ROLE_USER']],
            ['Sofia Patel', 'sofia@demo.com', 'Desert Debuggers', 'Software', 'Pending', ['ROLE_USER']],
            ['Noah Brooks', 'noah@demo.com', 'Desert Debuggers', 'Software', 'Checked In', ['ROLE_USER']],
            ['Eva Flores', 'eva@demo.com', null, 'Hardware', 'Pending', ['ROLE_USER']],
            ['Alex Kim', 'alex@demo.com', null, 'Software', 'Pending', ['ROLE_USER']],
            ['Judge One', 'judge1@demo.com', null, 'Software', 'Pending', ['ROLE_JUDGE']],
            ['Judge Two', 'judge2@demo.com', null, 'Hardware', 'Pending', ['ROLE_JUDGE']],
            ['Judge Three', 'judge3@demo.com', null, 'Software', 'Pending', ['ROLE_JUDGE']],
        ];

        foreach ($people as [$name, $email, $team, $track, $state, $roles]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setTeam($team);
            $user->setTrack($track);
            $user->setState($state);
            $user->setRoles($roles);

            // set a password so login works
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'password')
            );

            $manager->persist($user);
        }

        $manager->flush();
    }
}
