<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// Each judge in a pair scores separately. Code checks the value is between 0 and the criterion's max.
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_score_assignment_judge_criterion', fields: ['assignment', 'judge', 'criterion'])]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PanelAssignment $assignment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $judge = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Criterion $criterion = null;

    #[ORM\Column]
    private ?int $value = null;

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

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAssignment(): ?PanelAssignment
    {
        return $this->assignment;
    }

    public function setAssignment(?PanelAssignment $assignment): static
    {
        $this->assignment = $assignment;

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

    public function getCriterion(): ?Criterion
    {
        return $this->criterion;
    }

    public function setCriterion(?Criterion $criterion): static
    {
        $this->criterion = $criterion;

        return $this;
    }
}
