<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// Which team a panel judges in a round, and where.
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_panel_assignment_round_panel_team', fields: ['round', 'panel', 'team'])]
class PanelAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?JudgingRound $round = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Panel $panel = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $room = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $tableNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(?string $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getTableNumber(): ?string
    {
        return $this->tableNumber;
    }

    public function setTableNumber(?string $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getRound(): ?JudgingRound
    {
        return $this->round;
    }

    public function setRound(?JudgingRound $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getPanel(): ?Panel
    {
        return $this->panel;
    }

    public function setPanel(?Panel $panel): static
    {
        $this->panel = $panel;

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
}
