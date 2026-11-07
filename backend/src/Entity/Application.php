<?php

namespace App\Entity;

use App\Enum\ApplicationStatus;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\Mapping as ORM;

// One person applying to one event.
#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_application_user_event', fields: ['user', 'event'])]
#[ORM\Index(name: 'idx_application_event_status', fields: ['event', 'status'])]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(length: 16, enumType: ApplicationStatus::class)]
    private ApplicationStatus $status = ApplicationStatus::Submitted;

    #[ORM\Column(length: 128)]
    private ?string $school = null;

    #[ORM\Column(length: 128)]
    private ?string $major = null;

    // people who haven't been to RebelHacks before are beginners
    #[ORM\Column]
    private bool $attendedBefore = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?File $resume = null;

    // empty until they answer "Want team leaders to find you?"
    #[ORM\Column(nullable: true)]
    private ?bool $lookingForTeam = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $agreedToTermsAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $checkedInAt = null;

    public function __construct()
    {
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSchool(): ?string
    {
        return $this->school;
    }

    public function setSchool(string $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getMajor(): ?string
    {
        return $this->major;
    }

    public function setMajor(string $major): static
    {
        $this->major = $major;

        return $this;
    }

    public function isAttendedBefore(): ?bool
    {
        return $this->attendedBefore;
    }

    // Only RebelHacks counts: someone who's been to other hackathons but not this one is still a beginner.
    public function isBeginner(): bool
    {
        return !$this->attendedBefore;
    }

    public function setAttendedBefore(bool $attendedBefore): static
    {
        $this->attendedBefore = $attendedBefore;

        return $this;
    }

    public function isLookingForTeam(): ?bool
    {
        return $this->lookingForTeam;
    }

    public function setLookingForTeam(?bool $lookingForTeam): static
    {
        $this->lookingForTeam = $lookingForTeam;

        return $this;
    }

    public function getAgreedToTermsAt(): ?\DateTimeImmutable
    {
        return $this->agreedToTermsAt;
    }

    public function setAgreedToTermsAt(\DateTimeImmutable $agreedToTermsAt): static
    {
        $this->agreedToTermsAt = $agreedToTermsAt;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getCheckedInAt(): ?\DateTimeImmutable
    {
        return $this->checkedInAt;
    }

    public function setCheckedInAt(?\DateTimeImmutable $checkedInAt): static
    {
        $this->checkedInAt = $checkedInAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getResume(): ?File
    {
        return $this->resume;
    }

    public function setResume(?File $resume): static
    {
        $this->resume = $resume;

        return $this;
    }
}
