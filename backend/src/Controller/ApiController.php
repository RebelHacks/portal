<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login()
    {
        // This is intercepted by json_login in security.yaml
        // Controller code is never executed
        throw new \LogicException('Error logging in dude');
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $data = json_decode($request->getContent(), true);

        // Validate email
        if (!isset($data['email']) || empty($data['email'])) {
            return $this->json(['message' => 'Email is required'], 400);
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['message' => 'Email is already registered'], 400);
        }

        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid email format'], 400);
        }


        // Validate password
        if (!isset($data['password']) || empty($data['password'])) {
            return $this->json(['message' => 'Password is required'], 400);
        }

        if (strlen($data['password']) < 6 || strlen($data['password']) > 4096) {
            return $this->json(['message' => 'Password must be between 6 and 4096 characters long'], 400);
        }

        if ($data['password'] && preg_match('/\s/', $data['password'])) {
            return $this->json(['message' => 'Password cannot contain whitespace characters'], 400);
        }


        $user = (new User());
        $user->setEmail($data['email'])
            ->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ], 201);
    }

    // Get user json object
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function retrieveUsers(EntityManagerInterface $em)
    {
        $users = array_filter(
            $em->getRepository(User::class)->findAll(),
            fn(User $u) => !in_array('ROLE_JUDGE', $u->getRoles(), true)
        );

        $json_data = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'team' => $u->getTeam() ?? '',
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',

        ], $users);

        return $this->json($json_data);
    }

    #[Route('/judges', name: 'judges', methods: ['GET'])]
    public function retrieveJudges(EntityManagerInterface $em): JsonResponse
    {
        $judges = array_values(array_filter(
            $em->getRepository(User::class)->findAll(),
            fn(User $u) => in_array('ROLE_JUDGE', $u->getRoles(), true)
        ));

        $jsonData = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
        ], $judges);

        return $this->json($jsonData);
    }



    #[Route('/users/{id}', methods: ['PATCH'])]
    public function checkIn(
        Request $request,
        EntityManagerInterface $em,
        int $id
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $em->getRepository(User::class)->find($id);

        // Checks 
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $user->setState($data['state']);
        $em->flush();

        return $this->json($user);
    }



    #[Route('/teams', name: 'teams', methods: ['GET'])]
    public function retrieveTeams(EntityManagerInterface $em)
    {
        $teams = $em->getRepository(Team::class)->findAll();
        $users = $em->getRepository(User::class)->findAll();

        $membersByTeam = [];
        foreach ($users as $user) {
            $userTeam = $user->getTeam();
            if (!$userTeam) {
                continue;
            }

            $membersByTeam[$userTeam][] = [
                'id' => $user->getId(),
                'name' => $user->getName() ?? '',
                'email' => $user->getEmail(),
                'track' => $user->getTrack() ?? '',
                'state' => $user->getState() ?? 'Pending',
            ];
        }

        $json_data = array_map(fn(Team $t) => $this->serializeTeam(
            $t,
            $membersByTeam[$t->getTeamName() ?? ''] ?? []
        ), $teams);

        return $this->json($json_data);
    }

    #[Route('/teams/{id}', name: 'teams_update', methods: ['PATCH'])]
    public function updateTeam(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $currentName = $team->getTeamName();
        if (!$currentName) {
            return $this->json(['message' => 'Team has no current name'], 400);
        }

        if (array_key_exists('teamName', $data)) {
            $newTeamName = trim((string) $data['teamName']);
            if ($newTeamName === '') {
                return $this->json(['message' => 'Team name cannot be empty'], 400);
            }

            if ($newTeamName !== $currentName) {
                $existing = $em->getRepository(Team::class)->findOneBy(['teamName' => $newTeamName]);
                if ($existing && $existing->getId() !== $team->getId()) {
                    return $this->json(['message' => 'Team name already exists'], 409);
                }

                $members = $em->getRepository(User::class)->findBy(['team' => $currentName]);
                foreach ($members as $member) {
                    $member->setTeam($newTeamName);
                }

                $team->setTeamName($newTeamName);
                $currentName = $newTeamName;
            }
        }

        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            if (!in_array($status, ['Verified', 'Unverified'], true)) {
                return $this->json(['message' => 'Status must be Verified or Unverified'], 400);
            }

            $team->setStatus($status);
        }

        if (array_key_exists('track', $data)) {
            $track = (string) $data['track'];
            if (!in_array($track, ['Software', 'Hardware'], true)) {
                return $this->json(['message' => 'Track must be Software or Hardware'], 400);
            }

            $team->setTrack($track);
        }

        if (array_key_exists('projectName', $data)) {
            $projectName = trim((string) $data['projectName']);
            $team->setProjectName($projectName !== '' ? $projectName : null);
        }

        if (array_key_exists('projectDetails', $data)) {
            $projectDetails = trim((string) $data['projectDetails']);
            $team->setProjectDetails($projectDetails !== '' ? $projectDetails : null);
        }

        if (array_key_exists('assignments', $data)) {
            if (!is_array($data['assignments'])) {
                return $this->json(['message' => 'Assignments must be an object keyed by round id'], 400);
            }

            $normalizedAssignments = [];
            $allJudgeIds = [];
            foreach ($data['assignments'] as $roundId => $judgeIds) {
                if (!is_array($judgeIds)) {
                    return $this->json(['message' => 'Each round must map to an array of judge ids'], 400);
                }

                $normalizedJudgeIds = array_values(array_unique(array_map('intval', $judgeIds)));
                $normalizedAssignments[(string) $roundId] = $normalizedJudgeIds;
                $allJudgeIds = [...$allJudgeIds, ...$normalizedJudgeIds];
            }

            $allJudgeIds = array_values(array_unique($allJudgeIds));
            if ($allJudgeIds !== []) {
                $judgeUsers = $em->getRepository(User::class)->findBy(['id' => $allJudgeIds]);
                if (count($judgeUsers) !== count($allJudgeIds)) {
                    return $this->json(['message' => 'One or more judges were not found'], 404);
                }

                foreach ($judgeUsers as $judgeUser) {
                    if (!in_array('ROLE_JUDGE', $judgeUser->getRoles(), true)) {
                        return $this->json(['message' => 'Assignments must only include judge users'], 400);
                    }
                }
            }

            $team->setJudgeAssignments($normalizedAssignments);
        }

        $em->flush();

        $updatedMembers = $em->getRepository(User::class)->findBy(['team' => $currentName]);
        $jsonMembers = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',
        ], $updatedMembers);

        return $this->json($this->serializeTeam($team, $jsonMembers));
    }

    #[Route('/teams/{id}/members', name: 'teams_members_update', methods: ['PATCH'])]
    public function updateTeamMembers(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $memberIds = $data['memberIds'] ?? null;

        if (!is_array($memberIds)) {
            return $this->json(['message' => 'memberIds must be an array of user ids'], 400);
        }

        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        if (count($memberIds) > 5) {
            return $this->json(['message' => 'A team can only have up to 5 members'], 400);
        }

        $selectedUsers = [];
        if ($memberIds !== []) {
            $selectedUsers = $em->getRepository(User::class)->findBy(['id' => $memberIds]);
            if (count($selectedUsers) !== count($memberIds)) {
                return $this->json(['message' => 'One or more users were not found'], 404);
            }
        }

        $teamName = $team->getTeamName();
        if (!$teamName) {
            return $this->json(['message' => 'Team has no name and cannot be assigned'], 400);
        }

        $currentMembers = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        $selectedIdsLookup = array_fill_keys($memberIds, true);

        foreach ($currentMembers as $member) {
            if (!isset($selectedIdsLookup[$member->getId()])) {
                $member->setTeam(null);
            }
        }

        foreach ($selectedUsers as $member) {
            $member->setTeam($teamName);
        }

        $em->flush();

        $updatedMembers = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        $jsonMembers = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',
        ], $updatedMembers);

        return $this->json($this->serializeTeam($team, $jsonMembers));
    }

    private function serializeTeam(Team $team, array $members): array
    {
        return [
            'id' => $team->getId(),
            'teamName' => $team->getTeamName(),
            'status' => $team->getStatus() ?? 'Unverified',
            'track' => $team->getTrack() ?? 'Software',
            'project' => [
                'name' => $team->getProjectName() ?? '',
                'details' => $team->getProjectDetails() ?? '',
            ],
            'assignments' => $team->getJudgeAssignments() ?? [],
            'members' => $members,
        ];
    }
}
