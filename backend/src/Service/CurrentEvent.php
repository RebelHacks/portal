<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Contracts\Service\ResetInterface;

// The event that sign-up and the pages work with. Looked up once per request.
class CurrentEvent implements ResetInterface
{
    private ?Event $event = null;

    private bool $loaded = false;

    public function __construct(
        private EventRepository $events,
    ) {
    }

    public function get(): ?Event
    {
        if (!$this->loaded) {
            $this->event = $this->events->findCurrent();
            $this->loaded = true;
        }

        return $this->event;
    }

    public function reset(): void
    {
        $this->event = null;
        $this->loaded = false;
    }
}
