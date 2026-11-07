<?php

namespace App\Entity;

use App\Enum\InviteStatus;
use Doctrine\ORM\Mapping as ORM;

// A leader inviting one person from the "looking for a team" list.
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_team_invite_team_invitee', fields: ['team', 'invitee'])]
class TeamInvite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Application $invitee = null;

    #[ORM\Column(length: 16, enumType: InviteStatus::class)]
    private InviteStatus $status = InviteStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?InviteStatus
    {
        return $this->status;
    }

    public function setStatus(InviteStatus $status): static
    {
        $this->status = $status;

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

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getInvitee(): ?Application
    {
        return $this->invitee;
    }

    public function setInvitee(?Application $invitee): static
    {
        $this->invitee = $invitee;

        return $this;
    }
}
