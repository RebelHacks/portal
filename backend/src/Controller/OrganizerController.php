<?php

namespace App\Controller;

use App\Entity\Announcement;
use App\Entity\Application;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\ApplicationStatus;
use App\Repository\ApplicationRepository;
use App\Repository\TeamRepository;
use App\Service\CurrentHackathon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Organizer pages. Reads are real; every write is stubbed with a TODO. access_control keeps /organizer to ROLE_ADMIN.
// Anything that touches a person's data must also write an audit_log row (see the design doc).
#[Route('/organizer')]
class OrganizerController extends AbstractController
{
    private const PER_PAGE = 25;

    public function __construct(
        private CurrentHackathon $currentHackathon,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_organizer', methods: ['GET'])]
    public function home(ApplicationRepository $applications, TeamRepository $teams): Response
    {
        $hackathon = $this->currentHackathon->get();
        $counts = ['registered' => 0, 'verified' => 0, 'waitlisted' => 0, 'teams' => 0, 'noTeam' => 0, 'looking' => 0, 'noTrack' => 0];

        if ($hackathon !== null) {
            $qb = fn () => $this->em->createQueryBuilder()->select('COUNT(a.id)')->from(Application::class, 'a')->andWhere('a.hackathon = :h')->setParameter('h', $hackathon);
            $counts['registered'] = (int) $qb()->getQuery()->getSingleScalarResult();
            $counts['verified'] = (int) $qb()->join('a.user', 'u')->andWhere('u.isVerified = true')->getQuery()->getSingleScalarResult();
            $counts['waitlisted'] = (int) $qb()->andWhere('a.status = :s')->setParameter('s', ApplicationStatus::Waitlisted)->getQuery()->getSingleScalarResult();
            $counts['looking'] = (int) $qb()->andWhere('a.lookingForTeam = true')->getQuery()->getSingleScalarResult();
            $counts['teams'] = $teams->countAtHackathon($hackathon);
            $counts['noTrack'] = $teams->countByTrack($hackathon)[null] ?? 0;
            $onTeam = (int) $this->em->createQueryBuilder()->select('COUNT(m.id)')->from('App\Entity\TeamMember', 'm')->join('m.team', 't')->andWhere('t.hackathon = :h')->setParameter('h', $hackathon)->getQuery()->getSingleScalarResult();
            $counts['noTeam'] = $counts['registered'] - $onTeam;
        }

        return $this->render('organizer/home.html.twig', ['hackathon' => $hackathon, 'counts' => $counts]);
    }

    #[Route('/people', name: 'app_organizer_people', methods: ['GET'])]
    public function people(Request $request): Response
    {
        $hackathon = $this->currentHackathon->get();
        $page = max(1, $request->query->getInt('page', 1));
        $q = trim((string) $request->query->get('q', ''));

        $rows = [];
        $total = 0;
        if ($hackathon !== null) {
            $qb = $this->em->createQueryBuilder()->select('a', 'u')->from(Application::class, 'a')->join('a.user', 'u')
                ->andWhere('a.hackathon = :h')->setParameter('h', $hackathon)
                ->orderBy('u.lastName', 'ASC')->addOrderBy('u.firstName', 'ASC');
            if ($q !== '') {
                $qb->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')->setParameter('q', '%' . $q . '%');
            }
            // TODO: status, school and no-team filters
            $total = count($qb->getQuery()->getResult());
            $rows = $qb->setFirstResult(($page - 1) * self::PER_PAGE)->setMaxResults(self::PER_PAGE)->getQuery()->getResult();
        }

        return $this->render('organizer/people.html.twig', [
            'hackathon' => $hackathon, 'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => self::PER_PAGE, 'q' => $q,
        ]);
    }

    // TODO: status changes (email the person, audit log), the login link, deletion (the console command's rules).
    #[Route('/people/{id}', name: 'app_organizer_person', methods: ['GET', 'POST'])]
    public function person(Application $application): Response
    {
        $membership = $this->em->getRepository('App\Entity\TeamMember')->findOneBy(['application' => $application]);

        return $this->render('organizer/person.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'application' => $application,
            'team' => $membership?->getTeam(),
            // TODO: this person's audit_log rows
            'history' => [],
        ]);
    }

    #[Route('/teams', name: 'app_organizer_teams', methods: ['GET'])]
    public function teams(Request $request, TeamRepository $teams): Response
    {
        $hackathon = $this->currentHackathon->get();
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('organizer/teams.html.twig', [
            'hackathon' => $hackathon,
            'teams' => $hackathon ? $teams->findAtHackathon($hackathon, $page, self::PER_PAGE) : [],
            'total' => $hackathon ? $teams->countAtHackathon($hackathon) : 0,
            'page' => $page, 'perPage' => self::PER_PAGE,
        ]);
    }

    // TODO: rename, change track (no Beginner override), make leader, remove, reset link, disband. All logged.
    #[Route('/teams/{id}', name: 'app_organizer_team', methods: ['GET', 'POST'])]
    public function team(Team $team): Response
    {
        return $this->render('organizer/team.html.twig', [
            'hackathon' => $this->currentHackathon->get(),
            'team' => $team,
            'tracks' => $this->em->getRepository('App\Entity\Track')->findBy(['hackathon' => $team->getHackathon()], ['id' => 'ASC']),
        ]);
    }

    // TODO: the search (name or email, this hackathon), and the check-in write (checked_in_at, audit log).
    #[Route('/check-in', name: 'app_organizer_checkin', methods: ['GET', 'POST'])]
    public function checkIn(Request $request): Response
    {
        $hackathon = $this->currentHackathon->get();
        $q = trim((string) $request->query->get('q', ''));
        $matches = [];
        if ($hackathon !== null && $q !== '') {
            $matches = $this->em->createQueryBuilder()->select('a', 'u')->from(Application::class, 'a')->join('a.user', 'u')
                ->andWhere('a.hackathon = :h')->setParameter('h', $hackathon)
                ->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')->setParameter('q', '%' . $q . '%')
                ->setMaxResults(10)->getQuery()->getResult();
        }
        $checkedIn = $hackathon ? (int) $this->em->createQueryBuilder()->select('COUNT(a.id)')->from(Application::class, 'a')->andWhere('a.hackathon = :h')->andWhere('a.checkedInAt IS NOT NULL')->setParameter('h', $hackathon)->getQuery()->getSingleScalarResult() : 0;

        return $this->render('organizer/checkin.html.twig', ['hackathon' => $hackathon, 'q' => $q, 'matches' => $matches, 'checkedIn' => $checkedIn]);
    }

    // TODO: posting (save, then email everyone registered through app:email-registrants' mailer), deleting.
    #[Route('/announcements', name: 'app_organizer_announcements', methods: ['GET', 'POST'])]
    public function announcements(): Response
    {
        $hackathon = $this->currentHackathon->get();

        return $this->render('organizer/announcements.html.twig', [
            'hackathon' => $hackathon,
            'announcements' => $hackathon ? $this->em->getRepository(Announcement::class)->findBy(['hackathon' => $hackathon], ['publishedAt' => 'DESC']) : [],
        ]);
    }

    // TODO: panels, judges and assignments, once the judging tables are back.
    #[Route('/judging', name: 'app_organizer_judging', methods: ['GET'])]
    public function judging(): Response
    {
        $judges = $this->em->getRepository(User::class)->createQueryBuilder('u')->andWhere('u.roles LIKE :r')->setParameter('r', '%ROLE_JUDGE%')->orderBy('u.lastName')->getQuery()->getResult();

        return $this->render('organizer/judging.html.twig', ['hackathon' => $this->currentHackathon->get(), 'judges' => $judges]);
    }
}
