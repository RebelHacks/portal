<?php
namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class JudgeController extends AbstractController
{
    #[Route('/api/judge/teams', name: 'judge_teams', methods: ['GET'])]
    public function getTeams(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Auth required'], 401);
        }

        if (!in_array('ROLE_JUDGE', $user->getRoles(), true)) {
            return $this->json(['error' => 'Judge access required'], 403);
        }

        $judgeId = $user->getId();
        if (!$judgeId) {
            return $this->json(['error' => 'Judge identity missing'], 400);
        }

        $teams = $em->getRepository(Team::class)->findAll();
        $allUsers = $em->getRepository(User::class)->findAll();

        $membersByTeam = [];
        foreach ($allUsers as $member) {
            $teamName = $member->getTeam();
            if (!$teamName) {
                continue;
            }

            $membersByTeam[$teamName][] = [
                'id' => $member->getId(),
                'name' => $member->getName() ?? $member->getEmail(),
                'email' => $member->getEmail(),
            ];
        }

        $assignedTeams = [];
        foreach ($teams as $team) {
            $assignments = $team->getJudgeAssignments() ?? [];
            $isAssigned = false;
            foreach ($assignments as $judgeIds) {
                if (is_numeric($judgeIds) && (int) $judgeIds === $judgeId) {
                    $isAssigned = true;
                    break;
                }

                if (!is_array($judgeIds)) {
                    continue;
                }

                $normalizedIds = array_map('intval', $judgeIds);
                if (in_array($judgeId, $normalizedIds, true)) {
                    $isAssigned = true;
                    break;
                }
            }

            if (!$isAssigned) {
                continue;
            }

            $teamName = $team->getName() ?? '';
            $assignedTeams[] = [
                'id' => $team->getId(),
                'teamName' => $teamName,
                'projectName' => $team->getProjectName() ?? '',
                'projectDetails' => $team->getProjectDetails() ?? '',
                'members' => $membersByTeam[$teamName] ?? [],
                'application' => $team->getApplication() ?? 0,
                'technicality' => $team->getTechnicality() ?? 0,
                'creativity' => $team->getCreativity() ?? 0,
                'functionality' => $team->getFunctionality() ?? 0,
                'theme' => $team->isTheme() ?? false,
                'review' => $team->getReview() ?? '',
            ];
        }

        return $this->json([
            'judge' => [
                'id' => $judgeId,
                'name' => $user->getName() ?? $user->getEmail(),
            ],
            'teams' => $assignedTeams,
        ]);
    }
}
