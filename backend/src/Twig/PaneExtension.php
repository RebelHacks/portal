<?php

namespace App\Twig;

use App\Entity\Announcement;
use App\Entity\ScheduleItem;
use App\Repository\ScheduleItemRepository;
use App\Service\CurrentHackathon;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// What the news column shows on every page, so no controller has to pass it.
class PaneExtension extends AbstractExtension
{
    public function __construct(
        private CurrentHackathon $currentHackathon,
        private ScheduleItemRepository $items,
        private EntityManagerInterface $em,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pane_next', $this->next(...)),
            new TwigFunction('pane_announcements', $this->announcements(...)),
        ];
    }

    public function next(): ?ScheduleItem
    {
        $hackathon = $this->currentHackathon->get();

        return $hackathon ? $this->items->findNext($hackathon, new \DateTimeImmutable()) : null;
    }

    /** @return Announcement[] newest first, at most five */
    public function announcements(): array
    {
        $hackathon = $this->currentHackathon->get();

        return $hackathon ? $this->em->getRepository(Announcement::class)->findBy(['hackathon' => $hackathon], ['publishedAt' => 'DESC'], 5) : [];
    }
}
