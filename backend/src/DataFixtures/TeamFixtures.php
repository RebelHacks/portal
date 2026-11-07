<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\Event;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\Track;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

// "ZZ Test Team 01" to "25", each with a leader and one member, spread across the tracks.
class TeamFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $event = $this->getReference(EventFixtures::EVENT, Event::class);

        for ($i = 1; $i <= UserFixtures::TEST_TEAM_COUNT; $i++) {
            $trackName = EventFixtures::TRACKS[$i % count(EventFixtures::TRACKS)];
            $track = $this->getReference('track_' . $trackName, Track::class);

            $team = (new Team())
                ->setEvent($event)
                ->setName(sprintf('ZZ Test Team %02d', $i))
                ->setTrack($track)
                ->setInviteCode(bin2hex(random_bytes(8)));
            $manager->persist($team);

            $allBeginners = true;
            foreach (['lead', 'member'] as $kind) {
                $application = $this->getReference(UserFixtures::applicationReference($i, $kind), Application::class);
                $allBeginners = $allBeginners && $application->isBeginner();

                $member = (new TeamMember())
                    ->setApplication($application);
                $team->addMember($member);
                $manager->persist($member);

                if ($kind === 'lead') {
                    $team->setLeader($member);
                }
            }

            // The rule the app follows too: anyone who's been before keeps the team out of Beginner.
            if ($track->isBeginnersOnly() && !$allBeginners) {
                $team->setTrack($track->getFallbackTrack());
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
