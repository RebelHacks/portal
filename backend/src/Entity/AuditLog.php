<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// Organizer actions on people's data: status changes, roles given, resume downloads, exports, deletions.
// Rows are only ever added.
#[ORM\Entity]
#[ORM\Index(name: 'idx_audit_log_created_at', fields: ['createdAt'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Empty for commands run on the server. No cascade: an organizer's account can't be
    // deleted while the log points at it, so who did what is never erased. Demote them instead.
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $actor = null;

    #[ORM\Column(length: 64)]
    private ?string $action = null;

    // What was acted on, as a table and id ("application", 42), never a name or email:
    // deleting a person can't reach text copied into the log.
    #[ORM\Column(length: 32)]
    private ?string $subjectType = null;

    // empty for actions on many rows at once, like an export
    #[ORM\Column(nullable: true)]
    private ?int $subjectId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getSubjectType(): ?string
    {
        return $this->subjectType;
    }

    public function setSubjectType(string $subjectType): static
    {
        $this->subjectType = $subjectType;

        return $this;
    }

    public function getSubjectId(): ?int
    {
        return $this->subjectId;
    }

    public function setSubjectId(?int $subjectId): static
    {
        $this->subjectId = $subjectId;

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

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;

        return $this;
    }
}
