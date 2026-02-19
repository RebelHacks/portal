"use client";

import { useEffect, useState } from "react";
import api from "@/lib/api";

type TeamMember = {
  id: number;
  name: string;
  email: string;
};

type JudgeTeam = {
  id: number;
  teamName: string;
  projectName: string;
  projectDetails: string;
  members: TeamMember[];
  review: string;
  application: number;
  technicality: number;
  creativity: number;
  functionality: number;
  theme: boolean;
};

type JudgeTeamsResponse = {
  judge: {
    id: number;
    name: string;
  };
  teams: JudgeTeam[];
};

export default function JudgeDashboard() {
  const [teams, setTeams] = useState<JudgeTeam[]>([]);
  const [expandedTeamId, setExpandedTeamId] = useState<number | null>(null);
  const [judgeName, setJudgeName] = useState("Judge View");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [savingId, setSavingId] = useState<number | null>(null);

  useEffect(() => {
    let mounted = true;

    const loadJudgeTeams = async () => {
      try {
        const data = await api.get<JudgeTeamsResponse>("/judge/teams");
        if (!mounted) {
          return;
        }

        setJudgeName(data.judge?.name || "Judge View");
        setTeams(data.teams || []);
      } catch (err: any) {
        const message =
          err?.response?.data?.error ||
          err?.response?.data?.message ||
          "Could not load assigned teams.";
        if (mounted) {
          setError(message);
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    loadJudgeTeams();

    return () => {
      mounted = false;
    };
  }, []);

  const toggleExpanded = (teamId: number) => {
    setExpandedTeamId((prev) => (prev === teamId ? null : teamId));
  };

  const handleScoreChange = (
    teamId: number,
    field: "application" | "technicality" | "creativity" | "functionality" | "theme" | "review",
    value: string | boolean,
  ) => {
    setTeams((prev) =>
      prev.map((team) => {
        if (team.id !== teamId) {
          return team;
        }

        if (field === "theme") {
          return { ...team, theme: Boolean(value) };
        }

        if (field === "review") {
          return { ...team, review: String(value) };
        }

        return { ...team, [field]: Number(value) };
      }),
    );
  };

  const totalScore = (team: JudgeTeam) =>
    team.application +
    team.technicality +
    team.creativity +
    team.functionality +
    (team.theme ? 5 : 0);

  const submitReview = async (teamId: number) => {
    const team = teams.find((t) => t.id === teamId);
    if (!team) {
      return;
    }

    setSavingId(teamId);
    try {
      await api.post(`/teams/${teamId}/review`, {
        application: team.application,
        technicality: team.technicality,
        creativity: team.creativity,
        functionality: team.functionality,
        theme: team.theme,
        review: team.review,
      });

      alert(`Review for ${team.teamName} saved successfully.`);
    } catch (err: any) {
      const message =
        err?.response?.data?.error ||
        err?.response?.data?.message ||
        "Could not save review.";
      alert(message);
    } finally {
      setSavingId(null);
    }
  };

  if (loading) {
    return <main className="min-h-screen p-6 text-white">Loading assigned teams...</main>;
  }

  if (error) {
    return <main className="min-h-screen p-6 text-red-400">{error}</main>;
  }

  return (
    <main className="min-h-screen bg-dark-blue-900 p-6 text-white">
      <header className="mb-8 border-b-4 border-yellow-400 pb-4 text-center">
        <h1 className="text-3xl font-bold text-yellow-400">{judgeName}</h1>
        <p className="opacity-90">Assigned Teams & Reviews</p>
      </header>

      {teams.length === 0 ? (
        <p className="text-center text-white/80">No teams are assigned to you yet.</p>
      ) : (
        <section className="grid items-start gap-6 md:grid-cols-2 lg:grid-cols-3">
          {teams.map((team) => (
            <div
              key={team.id}
              className="rounded-2xl border-2 border-yellow-400 bg-white/5 p-5 shadow-lg"
            >
              <div className="mb-2 flex items-center justify-between">
                <h2 className="text-xl font-semibold text-yellow-400">{team.teamName}</h2>
                <button
                  onClick={() => toggleExpanded(team.id)}
                  className="text-yellow-400 transition-colors hover:text-yellow-300"
                >
                  {expandedTeamId === team.id ? "▼" : "▶"}
                </button>
              </div>

              {expandedTeamId === team.id && (
                <>
                  <p className="mb-1 font-medium">Project: {team.projectName || "N/A"}</p>
                  <p className="mb-2 text-sm text-white/80">{team.projectDetails || "No project details yet."}</p>
                  <p className="mb-3 text-sm text-white/90">
                    Members: {team.members.map((m) => m.name).join(", ") || "None"}
                  </p>

                  <textarea
                    placeholder="Enter review..."
                    value={team.review}
                    onChange={(e) => handleScoreChange(team.id, "review", e.target.value)}
                    className="mb-3 w-full rounded-md border border-white/20 bg-black/20 p-2 text-white"
                  />

                  <div className="mb-3 grid grid-cols-2 gap-2">
                    {(["application", "technicality", "creativity", "functionality"] as const).map(
                      (field) => (
                        <div key={field}>
                          <label className="mb-1 block text-sm capitalize">{field}</label>
                          <select
                            value={team[field]}
                            onChange={(e) => handleScoreChange(team.id, field, e.target.value)}
                            className="w-full rounded-md border border-white/20 bg-black/20 p-2 text-white"
                          >
                            {[0, 1, 2, 3, 4, 5].map((num) => (
                              <option key={num} value={num}>
                                {num}
                              </option>
                            ))}
                          </select>
                        </div>
                      ),
                    )}
                  </div>

                  <div className="mb-2">
                    <label className="inline-flex items-center gap-2">
                      <input
                        type="checkbox"
                        checked={team.theme}
                        onChange={(e) => handleScoreChange(team.id, "theme", e.target.checked)}
                        className="rounded"
                      />
                      Theme bonus (+5)
                    </label>
                  </div>

                  <p className="mb-2 font-bold text-yellow-400">Total Score: {totalScore(team)}</p>

                  <button
                    onClick={() => submitReview(team.id)}
                    disabled={savingId === team.id}
                    className="rounded-md bg-yellow-400 px-4 py-2 font-bold text-black disabled:opacity-60"
                  >
                    {savingId === team.id ? "Saving..." : "Save Review"}
                  </button>
                </>
              )}
            </div>
          ))}
        </section>
      )}
    </main>
  );
}
