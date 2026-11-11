<?php

namespace App\DataFixtures;

use App\Entity\Hackathon;
use App\Entity\ScheduleItem;
use App\Entity\Track;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// RebelHacks 2027, with sign-ups open so local testing works.
class HackathonFixtures extends Fixture
{
    public const HACKATHON = 'hackathon';

    // Only Beginner is public. The other tracks stay secret until they're announced,
    // so they don't get real names here.
    public const TRACKS = ['Beginner', 'Example Track 1', 'Example Track 2', 'Example Track 3'];

    // A sample schedule, not the real one: title, start, end (empty for a moment), location.
    private const SCHEDULE = [
        ['Check-in', '2027-02-20 09:00', '2027-02-20 10:00', 'SEB Auditorium'],
        ['Opening ceremony', '2027-02-20 10:00', '2027-02-20 11:00', 'SEB Auditorium'],
        ['Hacking starts', '2027-02-20 11:00', null, null],
        ['Lunch', '2027-02-20 12:30', '2027-02-20 13:30', null],
        ['Dinner', '2027-02-20 18:30', '2027-02-20 19:30', null],
        ['Breakfast', '2027-02-21 08:00', '2027-02-21 09:00', null],
        ['Hacking ends', '2027-02-21 11:00', null, null],
        ['Judging', '2027-02-21 11:30', '2027-02-21 14:30', null],
        ['Closing ceremony', '2027-02-21 16:30', '2027-02-21 17:30', 'SEB Auditorium'],
    ];

    public function load(ObjectManager $manager): void
    {
        $hackathon = (new Hackathon())
            ->setName('RebelHacks 2027')
            ->setStartsAt(self::vegasTime('2027-02-20 09:00'))
            ->setEndsAt(self::vegasTime('2027-02-21 18:00'))
            ->setRegistrationOpensAt(new \DateTimeImmutable('-1 day'))
            ->setRegistrationClosesAt(self::vegasTime('2027-02-19 23:59'))
            ->setMaxTeamSize(5)
            ->setCapacity(300);
        $manager->persist($hackathon);
        $this->addReference(self::HACKATHON, $hackathon);

        $tracks = [];
        foreach (self::TRACKS as $name) {
            $track = (new Track())
                ->setHackathon($hackathon)
                ->setName($name)
                ->setDescription($name === 'Beginner' ? 'For participants attending their first RebelHacks event' : null);
            $manager->persist($track);
            $this->addReference('track_' . $name, $track);
            $tracks[$name] = $track;
        }

        $tracks['Beginner']
            ->setBeginnersOnly(true)
            ->setFallbackTrack($tracks['Example Track 1']);

        foreach (self::SCHEDULE as [$title, $startsAt, $endsAt, $location]) {
            $manager->persist((new ScheduleItem())
                ->setHackathon($hackathon)
                ->setTitle($title)
                ->setStartsAt(self::vegasTime($startsAt))
                ->setEndsAt($endsAt !== null ? self::vegasTime($endsAt) : null)
                ->setLocation($location));
        }

        $manager->flush();
    }

    // Times are saved in UTC, so convert from Las Vegas time first.
    public static function vegasTime(string $time): \DateTimeImmutable
    {
        return (new \DateTimeImmutable($time, new \DateTimeZone('America/Los_Angeles')))
            ->setTimezone(new \DateTimeZone('UTC'));
    }
}
