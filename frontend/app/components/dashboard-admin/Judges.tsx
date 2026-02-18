"use client";

import { useEffect, useMemo, useState } from "react";
import api from "@/lib/api";
import type { Judge, Team } from "@/lib/types";
import styles from "../../dashboard-admin/admin.module.css";

type Round = {
  id: string;
  name: string;
};

type JudgeAssignment = {
  teamId: number;
  teamName: string;
  roundId: string;
  roundLabel: string;
};

const ROUNDS: Round[] = [
  { id: "r1", name: "Round 1" },
  { id: "r2", name: "Round 2" },
  { id: "r3", name: "Round 3" },
  { id: "r4", name: "Round 4" },
  { id: "r5", name: "Round 5" },
  { id: "r6", name: "Round 6" },
  { id: "r7", name: "Round 7" },
];

const ROUND_NAMES: Record<string, string> = {
  r1: "Round 1",
  r2: "Round 2",
  r3: "Round 3",
  r4: "Round 4",
  r5: "Round 5",
  r6: "Round 6",
  r7: "Round 7",
};

function labelForRound(roundId: string): string {
  return ROUND_NAMES[roundId] ?? roundId;
}

function normalizeTeam(team: Team): Team {
  return {
    ...team,
    assignments: team.assignments ?? {},
  };
}

export default function JudgesAdminPage() {
  const [judges, setJudges] = useState<Judge[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [loading, setLoading] = useState(true);
  const [savingAssignment, setSavingAssignment] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeJudgeId, setActiveJudgeId] = useState<number | null>(null);
  const [assignmentRound, setAssignmentRound] = useState<string>(ROUNDS[0].id);
  const [assignmentTeamId, setAssignmentTeamId] = useState<string>("");

  async function loadData() {
    setLoading(true);
    setError(null);
    try {
      const [judgeResponse, teamResponse] = await Promise.all([
        api.get<Judge[]>("/admin/judges"),
        api.get<Team[]>("/admin/teams"),
      ]);
      setJudges(judgeResponse);
      setTeams(teamResponse.map(normalizeTeam));
    } catch (err) {
      console.error(err);
      setError("Could not load judges right now.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadData();
  }, []);

  const activeJudge = useMemo(
    () => judges.find((judge) => judge.id === activeJudgeId) ?? null,
    [judges, activeJudgeId],
  );

  const sortedTeams = useMemo(() => [...teams].sort((a, b) => a.teamName.localeCompare(b.teamName)), [teams]);

  const displayRounds = useMemo(() => {
    const rounds = [...ROUNDS];
    const existingIds = new Set(rounds.map((round) => round.id));

    for (const team of teams) {
      for (const roundId of Object.keys(team.assignments ?? {})) {
        if (!existingIds.has(roundId)) {
          rounds.push({ id: roundId, name: labelForRound(roundId) });
          existingIds.add(roundId);
        }
      }
    }

    return rounds;
  }, [teams]);

  useEffect(() => {
    if (!assignmentTeamId && sortedTeams.length > 0) {
      setAssignmentTeamId(String(sortedTeams[0].id));
    }
  }, [assignmentTeamId, sortedTeams]);

  const assignmentsByJudge = useMemo(() => {
    const map = new Map<number, JudgeAssignment[]>();
    for (const judge of judges) {
      map.set(judge.id, []);
    }

    for (const team of teams) {
      const assignments = team.assignments ?? {};
      for (const [roundId, judgeIds] of Object.entries(assignments)) {
        for (const judgeId of judgeIds) {
          const current = map.get(judgeId) ?? [];
          current.push({
            teamId: team.id,
            teamName: team.teamName,
            roundId,
            roundLabel: labelForRound(roundId),
          });
          map.set(judgeId, current);
        }
      }
    }

    return map;
  }, [judges, teams]);

  const activeJudgeAssignments = useMemo(
    () => (activeJudge ? (assignmentsByJudge.get(activeJudge.id) ?? []) : []),
    [activeJudge, assignmentsByJudge],
  );

  function applyUpdatedTeam(updated: Team) {
    const normalized = normalizeTeam(updated);
    setTeams((current) => current.map((team) => (team.id === normalized.id ? normalized : team)));
  }

  function openEditor(judgeId: number) {
    setActiveJudgeId(judgeId);
    setAssignmentRound(ROUNDS[0].id);
    setAssignmentTeamId(sortedTeams[0] ? String(sortedTeams[0].id) : "");
  }

  function closeEditor() {
    setActiveJudgeId(null);
    setAssignmentRound(ROUNDS[0].id);
    setAssignmentTeamId("");
  }

  async function addAssignment() {
    if (!activeJudge) return;

    const teamId = Number(assignmentTeamId);
    if (!teamId) {
      setError("Please select a team.");
      return;
    }

    const team = teams.find((item) => item.id === teamId);
    if (!team) {
      setError("Selected team not found.");
      return;
    }

    const selected = team.assignments?.[assignmentRound] ?? [];
    if (selected.includes(activeJudge.id)) {
      setError("This judge is already assigned to that team for this round.");
      return;
    }

    setSavingAssignment(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/admin/teams/${team.id}`, {
        judgeAssignments: {
          ...(team.assignments ?? {}),
          [assignmentRound]: [...selected, activeJudge.id],
        },
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not add judge assignment.");
    } finally {
      setSavingAssignment(false);
    }
  }

  async function removeAssignment(judgeId: number, teamId: number, roundId: string) {
    const team = teams.find((item) => item.id === teamId);
    if (!team) return;

    const selected = team.assignments?.[roundId] ?? [];
    if (!selected.includes(judgeId)) return;

    const nextAssignments = { ...(team.assignments ?? {}) };
    const nextSelected = selected.filter((id) => id !== judgeId);
    if (nextSelected.length === 0) {
      delete nextAssignments[roundId];
    } else {
      nextAssignments[roundId] = nextSelected;
    }

    setSavingAssignment(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/admin/teams/${team.id}`, {
        judgeAssignments: nextAssignments,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not remove judge assignment.");
    } finally {
      setSavingAssignment(false);
    }
  }

  return (
    <>
      <h2 className={styles.primaryTitle}>Judges</h2>

      {error && (
        <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-(--sub-text)">
          {error}
        </div>
      )}

      <div className={`${styles.card} text-(--sub-text)`}>
        <div className="mb-3 flex items-center justify-between">
          <p className="text-sm text-(--sub-text)">Judge assignments by round and team.</p>
          <button onClick={() => void loadData()} className={`${styles.secondaryButton} text-xs`}>
            Refresh
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="opacity-70">
              <tr className="border-b border-white/10">
                <th className="py-3 text-left font-medium">Judge</th>
                <th className="py-3 text-left font-medium">Assignments</th>
                <th className="py-3 text-right font-medium">Total</th>
                <th className="py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {!loading &&
                judges.map((judge) => {
                  const items = assignmentsByJudge.get(judge.id) ?? [];
                  return (
                    <tr key={judge.id} className="border-b border-white/10 last:border-b-0">
                      <td className="py-3">
                        <div className="font-medium">{judge.name}</div>
                        <div className="text-xs opacity-70">{judge.email}</div>
                      </td>
                      <td className="py-3">
                        {items.length === 0 ? (
                          <span className="text-xs opacity-70">No judge assignments</span>
                        ) : (
                          <div className="flex flex-wrap gap-2">
                            {items.map((item) => (
                              <div
                                key={`${judge.id}-${item.roundId}-${item.teamId}`}
                                className="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs">
                                <span>
                                  {item.roundLabel} - {item.teamName}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => void removeAssignment(judge.id, item.teamId, item.roundId)}
                                  disabled={savingAssignment}
                                  className="rounded px-1 leading-none hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-60">
                                  ×
                                </button>
                              </div>
                            ))}
                          </div>
                        )}
                      </td>
                      <td className="py-3 text-right">{items.length}</td>
                      <td className="py-3 text-right">
                        <button
                          onClick={() => openEditor(judge.id)}
                          disabled={savingAssignment}
                          className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                          Assign
                        </button>
                      </td>
                    </tr>
                  );
                })}

              {loading && (
                <tr>
                  <td className="py-8 text-center text-(--sub-text)" colSpan={4}>
                    Loading judges...
                  </td>
                </tr>
              )}

              {!loading && judges.length === 0 && (
                <tr>
                  <td className="py-8 text-center text-(--sub-text)" colSpan={4}>
                    No judges found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {activeJudge && (
        <div className={`${styles.modalBackdrop} fixed inset-0 z-50 bg-black/50`} onMouseDown={closeEditor}>
          <div
            className={`${styles.cardModal} max-h-[90vh] w-[min(560px,95vw)] overflow-y-auto shadow-xl text-(--sub-text)`}
            onMouseDown={(event) => event.stopPropagation()}>
            <div className="mb-4 flex items-start justify-between gap-4">
              <div>
                <div className="text-lg font-semibold">Assign Judge</div>
                <div className="mt-1 text-sm">
                  {activeJudge.name} ({activeJudge.email})
                </div>
              </div>
              <button
                onClick={closeEditor}
                className="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs hover:opacity-80">
                ✕
              </button>
            </div>

            <div className="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-2 text-sm font-semibold">Add Assignment</div>
              <div className="grid grid-cols-1 gap-2 md:grid-cols-3">
                <select
                  value={assignmentRound}
                  onChange={(event) => setAssignmentRound(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                  {displayRounds.map((round) => (
                    <option key={round.id} value={round.id}>
                      {round.name}
                    </option>
                  ))}
                </select>

                <select
                  value={assignmentTeamId}
                  onChange={(event) => setAssignmentTeamId(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                  {sortedTeams.map((team) => (
                    <option key={team.id} value={team.id}>
                      {team.teamName}
                    </option>
                  ))}
                </select>

                <button
                  onClick={() => void addAssignment()}
                  disabled={savingAssignment || sortedTeams.length === 0}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                  {savingAssignment ? "Saving..." : "Add"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-2 text-sm font-semibold">Current Assignments</div>
              {activeJudgeAssignments.length === 0 ? (
                <div className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-(--sub-text)">
                  No assignments yet.
                </div>
              ) : (
                <div className="space-y-2">
                  {activeJudgeAssignments.map((item) => (
                    <div
                      key={`active-${activeJudge.id}-${item.roundId}-${item.teamId}`}
                      className="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 p-3 text-sm">
                      <span>
                        {item.roundLabel} - {item.teamName}
                      </span>
                      <button
                        onClick={() => void removeAssignment(activeJudge.id, item.teamId, item.roundId)}
                        disabled={savingAssignment}
                        className={`${styles.warnButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                        Remove
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
