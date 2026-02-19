<?php

namespace App\DataFixtures;

use App\Entity\Team;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TeamFixtures extends Fixture
{
    public const TEST_TEAM_COUNT = 25;

    public const TEST_TEAM_REF_PREFIX = 'zz_test_team_';

    public static function testTeamName(int $number): string
    {
        return sprintf('ZZ Test Team %02d', $number);
    }

    public static function testTeamReference(int $number): string
    {
        return self::TEST_TEAM_REF_PREFIX . sprintf('%02d', $number);
    }

    public function load(ObjectManager $manager): void
    {
        $teamRepository = $manager->getRepository(Team::class);

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
            $existing = $teamRepository->findOneBy(['name' => $item['name']]);
            if ($existing !== null) {
                continue;
            }

            $manager->persist(
                (new Team())
                    ->setName($item['name'])
                    ->setStatus($item['status'])
                    ->setTrack($item['track'])
                    ->setProjectName($item['project_name'])
                    ->setProjectDetails($item['project_details'])
                    ->setJudgeAssignments([
                        'r1' => [],
                        'r2' => [],
                    ])
            );
        }

        for ($i = 1; $i <= self::TEST_TEAM_COUNT; $i++) {
            $track = $i % 2 === 0 ? 'Hardware' : 'Software';
            $teamName = self::testTeamName($i);
            $team = $teamRepository->findOneBy(['name' => $teamName]);
            if ($team === null) {
                $team = (new Team())
                    ->setName($teamName)
                    ->setStatus('Verified')
                    ->setTrack($track)
                    ->setProjectName(sprintf('Fixture Project %02d', $i))
                    ->setProjectDetails('Fixture team for judge assignment testing.')
                    ->setJudgeAssignments([
                        'r1' => [],
                        'r2' => [],
                    ]);
                $manager->persist($team);
            }

            $this->addReference(self::testTeamReference($i), $team);
        }

        $manager->flush();
    }
}
