<?php

namespace App\Controller;

use App\Entity\Team; // Correct entity
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TeamReviewController extends AbstractController
{
    #[Route('/api/teams/{id}/review', name: 'team_submit_review', methods: ['POST'])]
    public function submitReview(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $judge = $this->getUser();
        if (!$judge instanceof User) {
            return $this->json(['error' => 'Auth required'], 401);
        }

        if (!in_array('ROLE_JUDGE', $judge->getRoles(), true)) {
            return $this->json(['error' => 'Judge access required'], 403);
        }

        // Find the team by ID
        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return $this->json(['error' => 'Team not found'], 404);
        }

        // Allow only judges assigned in at least one round for this team.
        if (!$this->isJudgeAssignedToTeam($team, $judge->getId() ?? 0)) {
            return $this->json(['error' => 'You are not assigned to review this team'], 403);
        }

        // Decode JSON payload from frontend
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        $application = $this->normalizeScore($data['application'] ?? 0);
        $technicality = $this->normalizeScore($data['technicality'] ?? 0);
        $creativity = $this->normalizeScore($data['creativity'] ?? 0);
        $functionality = $this->normalizeScore($data['functionality'] ?? 0);
        if ($application === null || $technicality === null || $creativity === null || $functionality === null) {
            return $this->json(['error' => 'Scores must be integers from 0 to 5'], 400);
        }

        // Update team review fields
        $team->setApplication($application);
        $team->setTechnicality($technicality);
        $team->setCreativity($creativity);
        $team->setFunctionality($functionality);
        $team->setTheme($data['theme'] ?? false);
        $team->setReview(trim((string) ($data['review'] ?? '')));

        // Persist changes
        $em->flush();

        // Return updated review data (optional)
        return $this->json([
            'success' => true,
            'team' => [
                'id' => $team->getId(),
                'application' => $team->getApplication(),
                'technicality' => $team->getTechnicality(),
                'creativity' => $team->getCreativity(),
                'functionality' => $team->getFunctionality(),
                'theme' => $team->isTheme(),
                'review' => $team->getReview(),
                'totalScore' =>
                    ($team->getApplication() ?? 0)
                    + ($team->getTechnicality() ?? 0)
                    + ($team->getCreativity() ?? 0)
                    + ($team->getFunctionality() ?? 0)
                    + (($team->isTheme() ?? false) ? 5 : 0),
            ]
        ]);
    }

    private function isJudgeAssignedToTeam(Team $team, int $judgeId): bool
    {
        if ($judgeId <= 0) {
            return false;
        }

        $assignments = $team->getJudgeAssignments() ?? [];
        foreach ($assignments as $judgeIds) {
            if (is_numeric($judgeIds) && (int) $judgeIds === $judgeId) {
                return true;
            }

            if (!is_array($judgeIds)) {
                continue;
            }

            if (in_array($judgeId, array_map('intval', $judgeIds), true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeScore(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $score = (int) $value;
        if ($score < 0 || $score > 5) {
            return null;
        }

        return $score;
    }
}
