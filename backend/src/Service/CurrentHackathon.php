<?php

namespace App\Service;

use App\Entity\Hackathon;
use App\Repository\HackathonRepository;
use Symfony\Contracts\Service\ResetInterface;

// The hackathon that sign-up and the pages work with. Looked up once per request.
class CurrentHackathon implements ResetInterface
{
    private ?Hackathon $hackathon = null;

    private bool $loaded = false;

    public function __construct(
        private HackathonRepository $hackathons,
    ) {
    }

    public function get(): ?Hackathon
    {
        if (!$this->loaded) {
            $this->hackathon = $this->hackathons->findCurrent();
            $this->loaded = true;
        }

        return $this->hackathon;
    }

    public function reset(): void
    {
        $this->hackathon = null;
        $this->loaded = false;
    }
}
