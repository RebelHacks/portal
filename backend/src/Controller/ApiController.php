<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    private const TEAM_user_LIMIT = 5;
    private const TEAM_NAME_MAX_LENGTH = 48;
    private const PROJECT_NAME_MAX_LENGTH = 100;
    private const PROJECT_DETAILS_MAX_LENGTH = 250;

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
        $content = $request->getContent();
        $data = json_decode($content, true);

        if ($data === null) {
            // Handle FormData
            $data = $request->request->all();
            $data['transcript'] = $request->files->get('transcript');
        }

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

        // Check if it's judge application or student registration
        $isJudge = isset($data['isJudge']) ? filter_var($data['isJudge'], FILTER_VALIDATE_BOOLEAN) : false;

        if ($isJudge) {
            // Judge application
            $user = (new User());
            $user->setEmail($data['email'])
                ->setRoles(['ROLE_JUDGE'])
                ->setState('Pending');

            $em->persist($user);
            $em->flush();

            return $this->json([
                'message' => 'Judge application submitted successfully',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail()
                ]
            ], 201);
        } else {
            // Student registration
            // Validate password
            if (!isset($data['password']) || empty($data['password'])) {
                return $this->json(['message' => 'Password is required'], 400);
            }

            if (strlen($data['password']) < 8 || strlen($data['password']) > 4096) {
                return $this->json(['message' => 'Password must be between 8 and 4096 characters long'], 400);
            }

            if ($data['password'] && preg_match('/\s/', $data['password'])) {
                return $this->json(['message' => 'Password cannot contain whitespace characters'], 400);
            }

            if ($data['password'] !== ($data['confirmPassword'] ?? '')) {
                return $this->json(['message' => 'Passwords do not match'], 400);
            }

            // Password Policy Rules
            if ($data['password'] && !preg_match('/[A-Z]/', $data['password'])) {
                return $this->json(['message' => 'Password must contain at least one uppercase letter'], 400);
            }

            if ($data['password'] && !preg_match('/[a-z]/', $data['password'])) {
                return $this->json(['message' => 'Password must contain at least one lowercase letter'], 400);
            }

            if ($data['password'] && !preg_match('/[0-9]/', $data['password'])) {
                return $this->json(['message' => 'Password must contain at least one number'], 400);
            }

            if ($data['password'] && !preg_match('/[\W_]/', $data['password'])) {
                return $this->json(['message' => 'Password must contain at least one special character'], 400);
            }

            // Validate username
            if (!isset($data['username']) || empty($data['username'])) {
                return $this->json(['message' => 'Username is required'], 400);
            }

            if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
                return $this->json(['message' => 'Username must be between 3 and 50 characters long'], 400);
            }

            if ($data['username'] && preg_match('/\s/', $data['username'])) {
                return $this->json(['message' => 'Username cannot contain whitespace characters'], 400);
            }

            $user = (new User());
            $user->setEmail($data['email'])
                ->setPassword($passwordHasher->hashPassword($user, $data['password']))
                ->setName($data['username'])
                ->setRoles(['ROLE_USER'])
                ->setState('Pending');

            if (isset($data['track'])) {
                $user->setTrack($data['track']);
            }

            if (isset($data['major'])) {
                $user->setMajor($data['major']);
            }

            // Handle transcript upload
            if (isset($data['transcript']) && $data['transcript'] instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $file = $data['transcript'];
                if ($file->getMimeType() !== 'application/pdf') {
                    return $this->json(['message' => 'Transcript must be a PDF file'], 400);
                }
                // Save file or store path
                $fileName = uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('kernel.project_dir') . '/var/uploads', $fileName);
                // Perhaps store path in user or separate table
            }

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
    }

    // Get user json object
    #[Route('/users', name: 'users', methods: ['GET'])]
    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function retrieveUsers(Request $request, EntityManagerInterface $em)
    {
        if ($this->isAdminRoute($request)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $users = array_values(array_filter(
            $em->getRepository(User::class)->findAll(),
            fn(User $u) => !in_array('ROLE_JUDGE', $u->getRoles(), true)
        ));

        $json_data = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'team' => $u->getTeam()?->getName() ?? '',
            'track' => $u->getTrack() ?? '',
            'major' => $u->getMajor() ?? '',
            'state' => $u->getState() ?? 'Pending',

        ], $users);

        return $this->json($json_data);
    }

    #[Route('/judges', name: 'judges', methods: ['GET'])]
    #[Route('/admin/judges', name: 'admin_judges', methods: ['GET'])]
    public function retrieveJudges(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($this->isAdminRoute($request)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

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

    #[Route('/users/profile', name: 'update_profile', methods: ['PATCH'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['track'])) {
            $user->setTrack((string) $data['track']);
        }

        if (isset($data['major'])) {
            $major = trim((string) $data['major']);
            $user->setMajor($major !== '' ? $major : null);
        }

        $em->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->getId(),
                'track' => $user->getTrack(),
                'major' => $user->getMajor(),
            ]
        ]);
    }

#[Route('/users/upload-file', name: 'upload_file', methods: ['POST'])]
public function uploadFile(
    Request $request,
    EntityManagerInterface $em
): JsonResponse {
    /** @var User $user */
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['message' => 'Unauthorized'], 401);
    }

    $file = $request->files->get('file');
    $type = $request->request->get('type'); // e.g., 'transcript', 'form', 'resume'

    if (!$file || !$type) {
        return $this->json(['message' => 'File and type are required'], 400);
    }

    // --- LOGIC FOR UNIQUENESS ---
    // Define which types should only have ONE file per user
    $uniqueTypes = ['transcript'];

    if (in_array($type, $uniqueTypes)) {
        $oldFile = $em->getRepository(File::class)->findOneBy([
            'user' => $user,
            'type' => $type
        ]);

        if ($oldFile) {
            $oldPath = $this->getParameter('kernel.project_dir') . $oldFile->getFilepath();
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $em->remove($oldFile);
            // We don't flush yet, we'll flush at the end
        }
    }
    // If the type is 'form', the logic above is skipped, and a new record is added.

    // --- FILE SAVING ---
    $fileName = sprintf('%s_%d_%s.%s', $type, $user->getId(), uniqid(), $file->guessExtension());
    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/files';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file->move($uploadDir, $fileName);

    $fileEntity = new File();
    $fileEntity->setUser($user);
    $fileEntity->setFilepath('/public/files/' . $fileName);
    $fileEntity->setType($type);

    $em->persist($fileEntity);
    $em->flush();

    return $this->json([
        'message' => 'File uploaded successfully',
        'fileId' => $fileEntity->getId(),
        'filepath' => $fileEntity->getFilepath()
    ], 200);
}


    #[Route('/users/{id}', methods: ['PATCH'])]
    #[Route('/admin/users/{id}', name: 'admin_user_checkin', methods: ['PATCH'])]
    public function checkIn(
        Request $request,
        EntityManagerInterface $em,
        int $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

    #[Route('/teams', name: 'create_team', methods: ['POST'])]
    public function createTeam(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser(); // Get current logged-in user
        
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        if ($user->getTeam()) {
            return $this->json(['message' => 'User is already in a team'], 400);
        }
    
        $data = json_decode($request->getContent(), true);
        $teamName = trim((string)($data['teamName'] ?? ''));
    
        // 1. Validation
        if ($teamName === '') {
            return $this->json(['message' => 'Team name is required'], 400);
        }
        if (mb_strlen($teamName) > self::TEAM_NAME_MAX_LENGTH) {
            return $this->json(['message' => 'Team name must be ' . self::TEAM_NAME_MAX_LENGTH . ' characters or fewer'], 400);
        }

        $existing = $em->getRepository(Team::class)->findOneBy(['name' => $teamName]);
        if ($existing) {
            return $this->json(['message' => 'Team name already exists'], 409);
        }
    
        // 2. Create the Team Entity
        $team = new Team();
        $team->setName($teamName);
        $team->setStatus('Unverified');
        $team->setTrack($data['track'] ?? 'Software');
        
        $em->persist($team);
    
        // 3. Assign User to Team (This marks them as a user/leader)
        // Based on your schema screenshot: user.team is a varchar(128)
        $user->setTeam($team); 
        
        // Add team leader role
        $currentRoles = array_diff($user->getRoles(), ['ROLE_USER']);
        $currentRoles[] = 'ROLE_TEAM_LEADER';
        $user->setRoles($currentRoles);
    
        $em->flush();
    
        // Return the created team with user data
        $users = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        $userData = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',
        ], $users);
        
        return $this->json($this->serializeTeam($team, $userData, $user->getId()), 201);
    }

    #[Route('/teams', name: 'teams', methods: ['GET'])]
    #[Route('/admin/teams', name: 'admin_teams', methods: ['GET'])]
    public function retrieveTeams(Request $request, EntityManagerInterface $em)
    {
        if ($this->isAdminRoute($request)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $teams = $em->getRepository(Team::class)->findAll();
        $users = $em->getRepository(User::class)->findAll();

        $usersByTeam = [];
        $leaderByTeam = [];
        foreach ($users as $user) {
            $userTeamName = $user->getTeam()?->getName();
            if (!$userTeamName) {
                continue;
            }

            $usersByTeam[$userTeamName][] = [
                'id' => $user->getId(),
                'name' => $user->getName() ?? '',
                'email' => $user->getEmail(),
                'track' => $user->getTrack() ?? '',
                'state' => $user->getState() ?? 'Pending',
            ];

            if (in_array('ROLE_TEAM_LEADER', $user->getRoles(), true)) {
                $leaderByTeam[$userTeamName] = $user->getId();
            }
        }

        $json_data = array_map(fn(Team $t) => $this->serializeTeam(
            $t,
            $usersByTeam[$t->getName() ?? ''] ?? [],
            $leaderByTeam[$t->getName() ?? ''] ?? null
        ), $teams);

        return $this->json($json_data);
    }

    #[Route('/teams/{id}', name: 'teams_update', methods: ['PATCH'])]
    #[Route('/admin/teams/{id}', name: 'admin_teams_update', methods: ['PATCH'])]
    public function updateTeam(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        if ($this->isAdminRoute($request)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $currentName = $team->getName();
        if (!$currentName) {
            return $this->json(['message' => 'Team has no current name'], 400);
        }

        if (array_key_exists('name', $data)) {
            $newTeamName = trim((string) $data['name']);
            if ($newTeamName === '') {
                return $this->json(['message' => 'Team name cannot be empty'], 400);
            }
            if (mb_strlen($newTeamName) > self::TEAM_NAME_MAX_LENGTH) {
                return $this->json(['message' => 'Team name must be ' . self::TEAM_NAME_MAX_LENGTH . ' characters or fewer'], 400);
            }

            if ($newTeamName !== $currentName) {
                $existing = $em->getRepository(Team::class)->findOneBy(['name' => $newTeamName]);
                if ($existing && $existing->getId() !== $team->getId()) {
                    return $this->json(['message' => 'Team name already exists'], 409);
                }

                $team->setName($newTeamName);
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
            if (mb_strlen($projectName) > self::PROJECT_NAME_MAX_LENGTH) {
                return $this->json(['message' => 'Project name must be ' . self::PROJECT_NAME_MAX_LENGTH . ' characters or fewer'], 400);
            }
            $team->setProjectName($projectName !== '' ? $projectName : null);
        }

        if (array_key_exists('projectDetails', $data)) {
            $projectDetails = trim((string) $data['projectDetails']);
            if (mb_strlen($projectDetails) > self::PROJECT_DETAILS_MAX_LENGTH) {
                return $this->json(['message' => 'Project details must be ' . self::PROJECT_DETAILS_MAX_LENGTH . ' characters or fewer'], 400);
            }
            $team->setProjectDetails($projectDetails !== '' ? $projectDetails : null);
        }

        $judgeAssignments = $data['judgeAssignments'] ?? $data['assignments'] ?? null;
        if ($judgeAssignments !== null) {
            if (!is_array($judgeAssignments)) {
                return $this->json(['message' => 'judgeAssignments must be an object keyed by round id'], 400);
            }

            $normalizedAssignments = [];
            $allJudgeIds = [];
            foreach ($judgeAssignments as $roundId => $judgeIds) {
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

        $updatedusers = $em->getRepository(User::class)->findBy(['team' => $currentName]);
        $jsonusers = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',
        ], $updatedusers);

        $leaderId = $this->findTeamLeaderId($em, $currentName);
        return $this->json($this->serializeTeam($team, $jsonusers, $leaderId));
    }

    #[Route('/teams/{id}/users', name: 'teams_users_update', methods: ['PATCH'])]
    #[Route('/admin/teams/{id}/users', name: 'admin_teams_users_update', methods: ['PATCH'])]
    public function updateTeamusers(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $isAdminRoute = $this->isAdminRoute($request);
        if ($isAdminRoute) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? null;

        if (!is_array($userIds)) {
            return $this->json(['message' => 'userIds must be an array of user ids'], 400);
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (count($userIds) > self::TEAM_user_LIMIT) {
            return $this->json(['message' => 'A team can only have up to ' . self::TEAM_user_LIMIT . ' users'], 400);
        }

        $selectedUsers = [];
        if ($userIds !== []) {
            $selectedUsers = $em->getRepository(User::class)->findBy(['id' => $userIds]);
            if (count($selectedUsers) !== count($userIds)) {
                return $this->json(['message' => 'One or more users were not found'], 404);
            }
        }

        $teamName = $team->getName();
        if (!$teamName) {
            return $this->json(['message' => 'Team has no name and cannot be assigned'], 400);
        }

        // Find the team leader
        $allUsers = $em->getRepository(User::class)->findAll();
        $leader = null;
        foreach ($allUsers as $u) {
            if ($u->getTeam()?->getName() === $teamName && in_array('ROLE_TEAM_LEADER', $u->getRoles())) {
                $leader = $u;
                break;
            }
        }
        if ($isAdminRoute) {
            // Admin can re-shape team usership; if leader is removed/missing, promote first selected user.
            if (!$leader || !in_array($leader->getId(), $userIds, true)) {
                $leader = null;
                if ($userIds !== []) {
                    $fallbackLeaderId = $userIds[0];
                    foreach ($selectedUsers as $selectedUser) {
                        if ($selectedUser->getId() === $fallbackLeaderId) {
                            $leader = $selectedUser;
                            break;
                        }
                    }
                }
            }
        } else {
            if (!$leader) {
                return $this->json(['message' => 'Team has no leader'], 400);
            }
            if (!in_array($leader->getId(), $userIds, true)) {
                return $this->json(['message' => 'Cannot remove team leader from team'], 400);
            }
        }

        // Check if selected users are not in another team
        foreach ($selectedUsers as $user) {
            if ($user->getTeam() && $user->getTeam() !== $teamName) {
                return $this->json(['message' => 'User ' . $user->getId() . ' is already in another team'], 400);
            }
        }

        $currentusers = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        $selectedIdsLookup = array_fill_keys($userIds, true);

        foreach ($currentusers as $user) {
            if (!isset($selectedIdsLookup[$user->getId()])) {
                $user->setTeam(null);
                $currentRoles = array_diff($user->getRoles(), ['ROLE_USER', 'ROLE_TEAM_LEADER', 'ROLE_user']);
                $user->setRoles($currentRoles);
            }
        }

        foreach ($selectedUsers as $user) {
            $user->setTeam($team);
        }

        // Set roles
        foreach ($selectedUsers as $user) {
            $currentRoles = array_diff($user->getRoles(), ['ROLE_USER', 'ROLE_TEAM_LEADER', 'ROLE_user']);
            if ($leader && $user->getId() === $leader->getId()) {
                if (!in_array('ROLE_TEAM_LEADER', $currentRoles)) {
                    $currentRoles[] = 'ROLE_TEAM_LEADER';
                }
            } else {
                if (!in_array('ROLE_user', $currentRoles)) {
                    $currentRoles[] = 'ROLE_user';
                }
            }
            $user->setRoles($currentRoles);
        }

        $em->flush();

        $updatedusers = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        $jsonusers = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName() ?? '',
            'email' => $u->getEmail(),
            'track' => $u->getTrack() ?? '',
            'state' => $u->getState() ?? 'Pending',
        ], $updatedusers);

        $leaderId = $this->findTeamLeaderId($em, $teamName);
        return $this->json($this->serializeTeam($team, $jsonusers, $leaderId));
    }

    #[Route('/teams/{id}', name: 'delete_team', methods: ['DELETE'])]
    #[Route('/admin/teams/{id}', name: 'admin_delete_team', methods: ['DELETE'])]
    public function deleteTeam(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        if ($this->isAdminRoute($request)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        $teamName = $team->getName();
        if ($teamName === null) {
            return $this->json(['message' => 'Team has no name'], 400);
        }

        if (!$this->isAdminRoute($request)) {
            /** @var User|null $user */
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['message' => 'Unauthorized'], 401);
            }

            if ($user->getTeam() !== $teamName) {
                return $this->json(['message' => 'Only team users can disband this team'], 403);
            }

            $users = $em->getRepository(User::class)->findBy(['team' => $teamName]);
            $isLeader = in_array('ROLE_TEAM_LEADER', $user->getRoles(), true);
            $isSolouser = count($users) === 1 && $users[0]->getId() === $user->getId();

            if (!$isLeader && !$isSolouser) {
                return $this->json(['message' => 'Only the team leader can disband the team'], 403);
            }
        }

        $users = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        foreach ($users as $user) {
            $user->setTeam(null);
            $currentRoles = array_diff($user->getRoles(), ['ROLE_USER', 'ROLE_TEAM_LEADER', 'ROLE_user']);
            $currentRoles[] = 'ROLE_USER';
            $user->setRoles(array_values(array_unique($currentRoles)));
        }

        // Decline all pending invitations for this team
        $pendingInvitations = $em->getRepository(Invitation::class)->findBy([
            'team' => $team,
            'status' => 'pending',
        ]);
        foreach ($pendingInvitations as $invitation) {
            $invitation->setStatus('declined');
        }

        $em->remove($team);
        $em->flush();

        return $this->json(['message' => 'Team deleted'], 200);
    }

    // ---- Invitation Endpoints ----

    #[Route('/invitations', name: 'create_invitation', methods: ['POST'])]
    public function createInvitation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $inviteeId = $data['inviteeId'] ?? null;

        if (!$inviteeId) {
            return $this->json(['message' => 'inviteeId is required'], 400);
        }

        // Sender must be a team leader
        if (!in_array('ROLE_TEAM_LEADER', $user->getRoles(), true)) {
            // Find team by user usership and leaderId
            $teams = $em->getRepository(Team::class)->findAll();
            $userTeam = null;
            foreach ($teams as $t) {
                $users = $em->getRepository(User::class)->findBy(['team' => $t->getName()]);
                foreach ($users as $m) {
                    if ($m->getId() === $user->getId()) {
                        $userTeam = $t;
                        break 2;
                    }
                }
            }
            if (!$userTeam) {
                return $this->json(['message' => 'You must be in a team to invite'], 400);
            }
            $leaderId = $this->findTeamLeaderId($em, $userTeam->getName());
            if ($leaderId !== $user->getId()) {
                return $this->json(['message' => 'Only the team leader can send invitations'], 403);
            }
        }

        // Find the team the user leads
        $userTeamName = $user->getTeam();
        if (!$userTeamName) {
            return $this->json(['message' => 'You are not in a team'], 400);
        }
        $team = $em->getRepository(Team::class)->findOneBy(['name' => $userTeamName]);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        // Check team capacity
        $currentusers = $em->getRepository(User::class)->findBy(['team' => $userTeamName]);
        $pendingInvites = $em->getRepository(Invitation::class)->findBy(['team' => $team, 'status' => 'pending']);
        if (count($currentusers) + count($pendingInvites) >= self::TEAM_user_LIMIT) {
            return $this->json(['message' => 'Team is at capacity (including pending invitations)'], 400);
        }

        $invitee = $em->getRepository(User::class)->find($inviteeId);
        if (!$invitee) {
            return $this->json(['message' => 'User not found'], 404);
        }

        if ($invitee->getTeam()) {
            return $this->json(['message' => 'User is already in a team'], 400);
        }

        // Check for duplicate pending invitation
        $existing = $em->getRepository(Invitation::class)->findOneBy([
            'team' => $team,
            'invitee' => $invitee,
            'status' => 'pending',
        ]);
        if ($existing) {
            return $this->json(['message' => 'Invitation already sent'], 409);
        }

        $invitation = new Invitation();
        $invitation->setTeam($team);
        $invitation->setInvitee($invitee);
        $invitation->setStatus('pending');

        $em->persist($invitation);
        $em->flush();

        return $this->json($this->serializeInvitation($invitation), 201);
    }

    #[Route('/invitations', name: 'list_invitations', methods: ['GET'])]
    public function listInvitations(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        $invitations = $em->getRepository(Invitation::class)->findBy([
            'invitee' => $user,
            'status' => 'pending',
        ]);

        $json = array_map(fn(Invitation $inv) => $this->serializeInvitation($inv), $invitations);

        return $this->json($json);
    }

    #[Route('/teams/{id}/invitations', name: 'list_team_invitations', methods: ['GET'])]
    public function listTeamInvitations(EntityManagerInterface $em, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['message' => 'Team not found'], 404);
        }

        // Check if user is a user of this team
        if ($user->getTeam() !== $team->getName()) {
            return $this->json(['message' => 'Access denied'], 403);
        }

        $invitations = $em->getRepository(Invitation::class)->findBy([
            'team' => $team,
            'status' => 'pending',
        ]);

        $json = array_map(fn(Invitation $inv) => $this->serializeInvitationWithUser($inv), $invitations);

        return $this->json($json);
    }

    #[Route('/invitations/{id}/accept', name: 'accept_invitation', methods: ['POST'])]
    public function acceptInvitation(EntityManagerInterface $em, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        $invitation = $em->getRepository(Invitation::class)->find($id);
        if (!$invitation || $invitation->getInvitee()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Invitation not found'], 404);
        }

        if ($invitation->getStatus() !== 'pending') {
            return $this->json(['message' => 'Invitation is no longer pending'], 400);
        }

        if ($user->getTeam()) {
            return $this->json(['message' => 'You are already in a team'], 400);
        }

        $team = $invitation->getTeam();
        $teamName = $team->getName();

        // Check team capacity
        $currentusers = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        if (count($currentusers) >= self::TEAM_user_LIMIT) {
            return $this->json(['message' => 'Team is already at maximum capacity'], 400);
        }

        // Add user to team
        $user->setTeam($team);
        $currentRoles = array_diff($user->getRoles(), ['ROLE_USER']);
        if (!in_array('ROLE_user', $currentRoles)) {
            $currentRoles[] = 'ROLE_user';
        }
        $user->setRoles($currentRoles);

        // Mark this invitation as accepted
        $invitation->setStatus('accepted');

        // Decline all other pending invitations for this user
        $otherInvitations = $em->getRepository(Invitation::class)->findBy([
            'invitee' => $user,
            'status' => 'pending',
        ]);
        foreach ($otherInvitations as $other) {
            if ($other->getId() !== $invitation->getId()) {
                $other->setStatus('declined');
            }
        }

        $em->flush();

        return $this->json(['message' => 'Invitation accepted'], 200);
    }

    #[Route('/invitations/{id}/decline', name: 'decline_invitation', methods: ['POST'])]
    public function declineInvitation(EntityManagerInterface $em, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Auth required'], 401);
        }

        $invitation = $em->getRepository(Invitation::class)->find($id);
        if (!$invitation || $invitation->getInvitee()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Invitation not found'], 404);
        }

        if ($invitation->getStatus() !== 'pending') {
            return $this->json(['message' => 'Invitation is no longer pending'], 400);
        }

        $invitation->setStatus('declined');
        $em->flush();

        return $this->json(['message' => 'Invitation declined'], 200);
    }

    private function serializeInvitation(Invitation $invitation): array
    {
        $team = $invitation->getTeam();
        return [
            'id' => $invitation->getId(),
            'teamId' => $team->getId(),
            'teamName' => $team->getName(),
            'status' => $invitation->getStatus(),
        ];
    }

    private function serializeInvitationWithUser(Invitation $invitation): array
    {
        $team = $invitation->getTeam();
        $invitee = $invitation->getInvitee();
        return [
            'id' => $invitation->getId(),
            'teamId' => $team->getId(),
            'teamName' => $team->getName(),
            'status' => $invitation->getStatus(),
            'invitee' => [
                'id' => $invitee->getId(),
                'name' => $invitee->getName() ?: $invitee->getEmail(),
                'email' => $invitee->getEmail(),
            ],
        ];
    }

    private function findTeamLeaderId(EntityManagerInterface $em, string $teamName): ?int
    {
        $users = $em->getRepository(User::class)->findBy(['team' => $teamName]);
        foreach ($users as $user) {
            if (in_array('ROLE_TEAM_LEADER', $user->getRoles(), true)) {
                return $user->getId();
            }
        }
        return null;
    }

    private function isAdminRoute(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/admin/');
    }

    private function serializeTeam(Team $team, array $users, ?int $leaderId = null): array
    {
        return [
            'id' => $team->getId(),
            'teamName' => $team->getName(),
            'status' => $team->getStatus() ?? 'Unverified',
            'track' => $team->getTrack() ?? 'Software',
            'project' => [
                'name' => $team->getProjectName() ?? '',
                'details' => $team->getProjectDetails() ?? '',
            ],
            'assignments' => $team->getJudgeAssignments() ?? [],
            'leaderId' => $leaderId,
            'users' => $users,
        ];
    }
}
