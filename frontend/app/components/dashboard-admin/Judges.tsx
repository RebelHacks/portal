"use client";

import { useEffect, useMemo, useState } from "react";
import api from "@/lib/api";
import type { Judge, Team } from "@/lib/types";
import styles from "../../dashboard-admin/admin.module.css";

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

export default function JudgesAdminPage() {
  const [judges, setJudges] = useState<Judge[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  async function loadData() {
    setLoading(true);
    setError(null);
    try {
      const [judgeResponse, teamResponse] = await Promise.all([
        api.get<Judge[]>("/judges"),
        api.get<Team[]>("/teams"),
      ]);
      setJudges(judgeResponse);
      setTeams(teamResponse);
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

  const assignmentsByJudge = useMemo(() => {
    const map = new Map<number, string[]>();
    for (const judge of judges) {
      map.set(judge.id, []);
    }

    for (const team of teams) {
      const assignments = team.assignments ?? {};
      for (const [roundId, judgeIds] of Object.entries(assignments)) {
        for (const judgeId of judgeIds) {
          const current = map.get(judgeId) ?? [];
          current.push(`${labelForRound(roundId)} - ${team.teamName}`);
          map.set(judgeId, current);
        }
      }
    }

    return map;
  }, [judges, teams]);

  return (
    <>
      <h2 className={styles.primaryTitle}>Judges</h2>

      {error && (
        <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">
          {error}
        </div>
      )}

      <div className={styles.card}>
        <div className="mb-3 flex items-center justify-between">
          <p className="text-sm text-white/70">
            Judge assignments by round and team.
          </p>
          <button
            onClick={() => void loadData()}
            className={`${styles.secondaryButton} text-xs`}
          >
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
                        <div className="text-xs text-white/60">{judge.email}</div>
                      </td>
                      <td className="py-3">
                        {items.length === 0 ? (
                          <span className="text-xs text-white/60">No judge assignments</span>
                        ) : (
                          <div className="flex flex-wrap gap-2">
                            {items.map((item) => (
                              <span
                                key={`${judge.id}-${item}`}
                                className="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs"
                              >
                                {item}
                              </span>
                            ))}
                          </div>
                        )}
                      </td>
                      <td className="py-3 text-right">{items.length}</td>
                    </tr>
                  );
                })}

              {loading && (
                <tr>
                  <td className="py-8 text-center text-white/70" colSpan={3}>
                    Loading judges...
                  </td>
                </tr>
              )}

              {!loading && judges.length === 0 && (
                <tr>
                  <td className="py-8 text-center text-white/70" colSpan={3}>
                    No judges found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
