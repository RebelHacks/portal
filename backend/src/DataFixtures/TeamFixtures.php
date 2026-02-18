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
                'project_name' => 'GlowFlow',
                'project_details' => 'Realtime queue tracker for event operations.',
            ],
            [
                'name' => 'Circuit Cowboys',
                'status' => 'Unverified',
                'track' => 'Hardware',
                'project_name' => 'VoltVault',
                'project_details' => 'Battery health dashboard and alerting.',
            ],
            [
                'name' => 'Desert Debuggers',
                'status' => 'Verified',
                'track' => 'Software',
                'project_name' => 'Dune Deploy',
                'project_details' => 'Deploy monitor for student hackathon projects.',
            ],
            [
                'name' => 'Byte Bandits',
                'status' => 'Unverified',
                'track' => 'Hardware',
                'project_name' => null,
                'project_details' => null,
            ],
        ];

        foreach ($teams as $item) {
            $team = (new Team())
                ->setTeamName($item['name'])
                ->setStatus($item['status'])
                ->setTrack($item['track'])
                ->setProjectName($item['project_name'])
                ->setprojectDetails($item['project_details'])
                ->setJudgeAssignments([
                    'r1' => [],
                    'r2' => [],
                ]);

            $manager->persist($team);
        }

        $manager->flush();
    }
}
