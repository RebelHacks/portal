<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// A team's project write-up. Anyone on the team can edit it, so the leader isn't a bottleneck.
// The project is still submitted and judged on Devpost; this is what judges read before they visit.
#[ORM\Entity]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // One per team. Deleting the team takes its write-up with it.
    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $projectName = null;

    #[ORM\Column(length: 1500, nullable: true)]
    private ?string $description = null;

    // free text, comma-separated: "Python, OpenCV, Flask"
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tech = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $githubUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $devpostUrl = null;

    // who saved last, so the team can see; kept if that account is deleted
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): static
    {
        $this->team = $team;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTech(): ?string
    {
        return $this->tech;
    }

    public function setTech(?string $tech): static
    {
        $this->tech = $tech;

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): static
    {
        $this->githubUrl = $githubUrl;

        return $this;
    }

    public function getDevpostUrl(): ?string
    {
        return $this->devpostUrl;
    }

    public function setDevpostUrl(?string $devpostUrl): static
    {
        $this->devpostUrl = $devpostUrl;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
