<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
//added for judge

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Team $team = null;
    
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $is_verified = false;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $track = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $major = null;

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $roles[] = 'ROLE_USER';
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $is_verified): static
    {
        $this->is_verified = $is_verified;

        return $this;
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


    public function getTrack(): ?string
    {
        return $this->track;
    }

    public function setTrack(string $track): static
    {
        $this->track = $track;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getMajor(): ?string
    {
        return $this->major;
    }

    public function setMajor(?string $major): static
    {
        $this->major = $major;

        return $this;
    }

<<<<<<< HEAD
    //below code added for judge
    public function setApplication(?int $value): static { $this->application = $value; return $this; }
    public function setTechnicality(?int $value): static { $this->technicality = $value; return $this; }
    public function setCreativity(?int $value): static { $this->creativity = $value; return $this; }
    public function setFunctionality(?int $value): static { $this->functionality = $value; return $this; }
    public function setTheme(?bool $value): static { $this->theme = $value; return $this; }
    public function setReview(?string $value): static { $this->review = $value; return $this; }
=======
    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }
>>>>>>> 10f0963b32406b2eda195c22421e85cf5647d33c
}
