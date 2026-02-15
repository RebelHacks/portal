<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: '`team`')]
#[ORM\UniqueConstraint(name: 'UNIQ_TEAM_NAME', fields: ['teamName'])]
class Team
{

    public const USER_ROLE_LEADER = 'ROLE_TEAM_LEADER';
    public const USER_ROLE_MEMBER = 'ROLE_TEAM_MEMBER';

    // Remove hardcoded strings
    // Make any options/constants here for the team entity, such as track options, status options, etc.

    // for majors
    public const MAJOR_CS = 'Computer Science';

    // Might need this for multiple.
    // public const array MAJORS = [
    //     self::MAJOR_CS,
    //     'Computer Engineering',
    //     'Electrical Engineering',
    //     'Other',
    // ];



        public const STATUS_UNVERIFIED = 'Unverified';
    public const STATUS_VERIFIED = 'Verified';

    public const STATUS = [
        self::STATUS_UNVERIFIED,
        self::STATUS_VERIFIED
    ];

    public const TRACK_SOFTWARE = 'Software';
    public const TRACK_HARDWARE = 'Hardware';
    public const UNVERIFIED = 'Unverified';
    public const VERIFIED = 'Verified';


    public const TRACKS = [
        self::TRACK_SOFTWARE,
        self::TRACK_HARDWARE,
    ];


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    private ?string $teamName = null;

    #[ORM\Column(length: 32, options: ['default' => 'Unverified'])]
    private ?string $status = UNVERIFIED;

    #[ORM\Column(length: 32, options: ['default' => 'Software'])]
    private ?string $track = TRACK_SOFTWARE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $projectDetails = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $judgeAssignments = [];

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTeamName(): ?string
    {
        return $this->teamName;
    }

    public function setTeamName(string $teamName): static
    {
        $this->teamName = $teamName;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTrack(): ?string
    {
        return $this->track;
    }

    public function setTrack(string $track): static
    {
        $this->track = $track;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(?string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getProjectDetails(): ?string
    {
        return $this->projectDetails;
    }

    public function setProjectDetails(?string $projectDetails): static
    {
        $this->projectDetails = $projectDetails;

        return $this;
    }

    public function getJudgeAssignments(): ?array
    {
        return $this->judgeAssignments ?? [];
    }

    public function setJudgeAssignments(?array $judgeAssignments): static
    {
        $this->judgeAssignments = $judgeAssignments ?? [];

        return $this;
    }
}
