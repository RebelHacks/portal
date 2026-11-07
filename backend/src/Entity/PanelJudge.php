<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_panel_judge_panel_judge', fields: ['panel', 'judge'])]
class PanelJudge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Panel $panel = null;

    // a user with ROLE_JUDGE
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $judge = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getJudge(): ?User
    {
        return $this->judge;
    }

    public function setJudge(?User $judge): static
    {
        $this->judge = $judge;

        return $this;
    }
}
