<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

// One RebelHacks. The current one is the event that starts last (see CurrentEvent).
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Index(name: 'idx_event_starts_at', fields: ['startsAt'])]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $registrationOpensAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $registrationClosesAt = null;

    #[ORM\Column]
    private int $maxTeamSize = 5;

    // How many applications can be accepted. Anyone verified after that is waitlisted.
    #[ORM\Column]
    private ?int $capacity = null;

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

    public function setEndsAt(\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getRegistrationOpensAt(): ?\DateTimeImmutable
    {
        return $this->registrationOpensAt;
    }

    public function setRegistrationOpensAt(\DateTimeImmutable $registrationOpensAt): static
    {
        $this->registrationOpensAt = $registrationOpensAt;

        return $this;
    }

    public function getRegistrationClosesAt(): ?\DateTimeImmutable
    {
        return $this->registrationClosesAt;
    }

    public function setRegistrationClosesAt(\DateTimeImmutable $registrationClosesAt): static
    {
        $this->registrationClosesAt = $registrationClosesAt;

        return $this;
    }

    public function getMaxTeamSize(): ?int
    {
        return $this->maxTeamSize;
    }

    public function setMaxTeamSize(int $maxTeamSize): static
    {
        $this->maxTeamSize = $maxTeamSize;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    // Open from the opening time up to, but not including, the closing time.
    public function isRegistrationOpen(\DateTimeImmutable $now): bool
    {
        return $now >= $this->registrationOpensAt && $now < $this->registrationClosesAt;
    }
}
