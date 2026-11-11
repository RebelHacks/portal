<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\TeamRepository;
use Doctrine\ORM\Mapping as ORM;

// The project's details are on Submission; the project itself is judged on Devpost.
// Delete a team in code (members first, then the team). The database won't cascade it,
// because the team and its leader point at each other.
#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_team_hackathon_name', fields: ['hackathon', 'name'])]
#[ORM\Index(name: 'idx_team_hackathon_track', fields: ['hackathon', 'track'])]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hackathon $hackathon = null;

    // TODO: settle the limit when the team card is designed. The team form has to check the same length.
    #[ORM\Column(length: 48)]
    private ?string $name = null;

    // one column, so a team can't have two leaders. Empty only while a new team is being saved.
    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TeamMember $leader = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Track $track = null;

    // for the invite link; the leader or an organizer can reset it
    #[ORM\Column(length: 32, unique: true)]
    private ?string $inviteCode = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'team')]
    private Collection $members;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(string $inviteCode): static
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getHackathon(): ?Hackathon
    {
        return $this->hackathon;
    }

    public function setHackathon(?Hackathon $hackathon): static
    {
        $this->hackathon = $hackathon;

        return $this;
    }

    public function getLeader(): ?TeamMember
    {
        return $this->leader;
    }

    public function setLeader(?TeamMember $leader): static
    {
        $this->leader = $leader;

        return $this;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): static
    {
        $this->track = $track;

        return $this;
    }

    /**
     * @return Collection<int, TeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(TeamMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(TeamMember $member): static
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getTeam() === $this) {
                $member->setTeam(null);
            }
        }

        return $this;
    }
}
