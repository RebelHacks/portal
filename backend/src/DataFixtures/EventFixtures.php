<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Track;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// RebelHacks 2027, with sign-ups open so local testing works.
class EventFixtures extends Fixture
{
    public const EVENT = 'event';

    // Only Beginner is public. The other tracks stay secret until they're announced,
    // so they don't get real names here.
    public const TRACKS = ['Beginner', 'Example Track 1', 'Example Track 2', 'Example Track 3'];

    public function load(ObjectManager $manager): void
    {
        $event = (new Event())
            ->setName('RebelHacks 2027')
            ->setStartsAt(self::vegasTime('2027-02-20 09:00'))
            ->setEndsAt(self::vegasTime('2027-02-21 18:00'))
            ->setRegistrationOpensAt(new \DateTimeImmutable('-1 day'))
            ->setRegistrationClosesAt(self::vegasTime('2027-02-19 23:59'))
            ->setMaxTeamSize(5)
            ->setCapacity(200);
        $manager->persist($event);
        $this->addReference(self::EVENT, $event);

        $tracks = [];
        foreach (self::TRACKS as $name) {
            $track = (new Track())
                ->setEvent($event)
                ->setName($name)
                ->setDescription($name === 'Beginner' ? 'For participants attending their first RebelHacks event' : null);
            $manager->persist($track);
            $this->addReference('track_' . $name, $track);
            $tracks[$name] = $track;
        }

        $tracks['Beginner']
            ->setBeginnersOnly(true)
            ->setFallbackTrack($tracks['Example Track 1']);

        $manager->flush();
    }

    // Times are saved in UTC, so convert from Las Vegas time first.
    public static function vegasTime(string $time): \DateTimeImmutable
    {
        return (new \DateTimeImmutable($time, new \DateTimeZone('America/Los_Angeles')))
            ->setTimezone(new \DateTimeZone('UTC'));
    }
}
