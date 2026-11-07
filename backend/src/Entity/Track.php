<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

// Every team is in the grand prize automatically, so it isn't a track.
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_track_event_name', fields: ['event', 'name'])]
class Track
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    // A flag rather than a name check, so renaming the track can't switch the rule off.
    #[ORM\Column(options: ['default' => false])]
    private bool $beginnersOnly = false;

    // Where a team in this track goes when someone who isn't a beginner joins it.
    // Organizers set it in the data, so no track name lives in the code.
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Track $fallbackTrack = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function isBeginnersOnly(): bool
    {
        return $this->beginnersOnly;
    }

    public function setBeginnersOnly(bool $beginnersOnly): static
    {
        $this->beginnersOnly = $beginnersOnly;

        return $this;
    }

    public function getFallbackTrack(): ?Track
    {
        return $this->fallbackTrack;
    }

    public function setFallbackTrack(?Track $fallbackTrack): static
    {
        $this->fallbackTrack = $fallbackTrack;

        return $this;
    }
}
