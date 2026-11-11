<?php

namespace App\Entity;

use App\Repository\ScheduleItemRepository;
use Doctrine\ORM\Mapping as ORM;

// One thing on a hackathon's schedule, like check-in or lunch.
#[ORM\Entity(repositoryClass: ScheduleItemRepository::class)]
#[ORM\Index(name: 'idx_schedule_item_hackathon_starts_at', fields: ['hackathon', 'startsAt'])]
class ScheduleItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hackathon $hackathon = null;

    #[ORM\Column(length: 128)]
    private ?string $title = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startsAt = null;

    // Empty for a moment instead of a span, like "Hacking ends".
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $location = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }
}
