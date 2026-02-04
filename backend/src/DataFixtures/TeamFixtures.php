<?php

namespace App\DataFixtures;

use App\Entity\Team;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TeamFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $teams = [
            [
                'name' => 'Neon Ninjas',
                'status' => 'Verified',
                'track' => 'Software',
                'projectName' => 'GlowFlow',
                'projectDetails' => 'Realtime queue tracker for event operations.',
            ],
            [
                'name' => 'Circuit Cowboys',
                'status' => 'Unverified',
                'track' => 'Hardware',
                'projectName' => 'VoltVault',
                'projectDetails' => 'Battery health dashboard and alerting.',
            ],
            [
                'name' => 'Desert Debuggers',
                'status' => 'Verified',
                'track' => 'Software',
                'projectName' => 'Dune Deploy',
                'projectDetails' => 'Deploy monitor for student hackathon projects.',
            ],
            [
                'name' => 'Byte Bandits',
                'status' => 'Unverified',
                'track' => 'Hardware',
                'projectName' => null,
                'projectDetails' => null,
            ],
        ];

        foreach ($teams as $item) {
            $team = (new Team())
                ->setTeamName($item['name'])
                ->setStatus($item['status'])
                ->setTrack($item['track'])
                ->setProjectName($item['projectName'])
                ->setProjectDetails($item['projectDetails'])
                ->setJudgeAssignments([
                    'r1' => [],
                    'r2' => [],
                ]);

            $manager->persist($team);
        }

        $manager->flush();
    }
}
