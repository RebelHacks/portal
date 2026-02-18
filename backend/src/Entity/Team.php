<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: '`team`')]
#[ORM\UniqueConstraint(name: 'UNIQ_TEAM_NAME', fields: ['name'])]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    private ?string $name = null;

    #[ORM\Column(length: 32, options: ['default' => 'Unverified'])]
    private ?string $status = 'Unverified';

    #[ORM\Column(length: 32, options: ['default' => 'Software'])]
    private ?string $track = 'Software';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $project_name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $project_details = null;

    #[ORM\Column(type: Types::JSON, nullable: true)] // dont do this 
    private ?array $judge_assignments = [];

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'team')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
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
        return $this->project_name;
    }

    public function setProjectName(?string $project_name): static
    {
        $this->project_name = $project_name;

        return $this;
    }

    public function getProjectDetails(): ?string
    {
        return $this->project_details;
    }

    public function setProjectDetails(?string $project_details): static
    {
        $this->project_details = $project_details;

        return $this;
    }

    public function getJudgeAssignments(): ?array
    {
        return $this->judge_assignments ?? [];
    }

    public function setJudgeAssignments(?array $judge_assignments): static
    {
        $this->judge_assignments = $judge_assignments ?? [];

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setTeam($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getTeam() === $this) {
                $user->setTeam(null);
            }
        }

        return $this;
    }
}
