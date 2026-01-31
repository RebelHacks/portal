"use client";

import { useMemo, useState } from "react";
import styles from "../../dashboard-admin/admin.module.css";

type Track = "Software" | "Hardware";

type Member = {
  id: string;
  name: string;
  email: string;
};

type TeamStatus = "Ready" | "Checked In" | "Incomplete";

type Team = {
  id: string;
  team: string;
  track: Track;
  status: TeamStatus;
  members: Member[];
};

export default function EditTeams() {
  const [search, setSearch] = useState("");
  const [trackFilter, setTrackFilter] = useState<"All" | Track>("All");

  const [teams, setTeams] = useState<Team[]>([
    {
      id: "t1",
      team: "Neon Ninjas",
      track: "Software",
      status: "Ready",
      members: [
        { id: "m1", name: "Ava Nguyen", email: "ava.nguyen@unlv.edu" },
        { id: "m2", name: "Liam Chen", email: "liam.chen@unlv.edu" },
        { id: "m3", name: "Priya Shah", email: "priya.shah@unlv.edu" },
      ],
    },
    {
      id: "t2",
      team: "Circuit Cowboys",
      track: "Hardware",
      status: "Checked In",
      members: [
        { id: "m4", name: "Mateo Rivera", email: "mateo.rivera@unlv.edu" },
        { id: "m5", name: "Maria Gonzalez", email: "maria.gonzalez@csn.edu" },
      ],
    },
    {
      id: "t3",
      team: "Desert Debuggers",
      track: "Software",
      status: "Incomplete",
      members: [
        { id: "m6", name: "Sofia Patel", email: "sofia.patel@unlv.edu" },
      ],
    },
  ]);

  const [activeTeamId, setActiveTeamId] = useState<string | null>(null);
  const activeTeam = teams.find((t) => t.id === activeTeamId) ?? null;

  const filteredTeams = useMemo(() => {
    const q = search.trim().toLowerCase();

    return teams
      .filter((t) => {
        if (trackFilter === "All") return true;
        return t.track === trackFilter;
      })
      .filter((t) => {
        if (!q) return true;
        return (
          t.team.toLowerCase().includes(q) ||
          t.members.some(
            (m) =>
              m.name.toLowerCase().includes(q) ||
              m.email.toLowerCase().includes(q),
          )
        );
      });
  }, [teams, search, trackFilter]);

  function removeMember(teamId: string, memberId: string) {
    setTeams((prev) =>
      prev.map((t) =>
        t.id === teamId
          ? { ...t, members: t.members.filter((m) => m.id !== memberId) }
          : t,
      ),
    );
  }

  function removeTeam(teamId: string) {
    setTeams((prev) => prev.filter((t) => t.id !== teamId));
    setActiveTeamId((cur) => (cur === teamId ? null : cur));
  }

  function statusChip(status: TeamStatus) {
    const cls =
      status === "Ready"
        ? "border-green-400/30 bg-green-500/10 text-green-300"
        : status === "Checked In"
          ? "border-yellow-400/30 bg-yellow-500/10 text-yellow-300"
          : "border-red-400/30 bg-red-500/10 text-red-300";

    return (
      <span
        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${cls}`}
      >
        {status}
      </span>
    );
  }

  return (
    <>
      <h2 className={styles.primaryTitle}>Teams</h2>

      <div className={styles.card}>
        {/* Controls */}
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div className="flex flex-col gap-2 md:flex-row">
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm"
              placeholder="Search teams, member name, member email..."
            />

            <select
              value={trackFilter}
              onChange={(e) => setTrackFilter(e.target.value as "All" | Track)}
              className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm [color-scheme:dark]"
            >
              <option value="All">All Tracks</option>
              <option value="Software">Software</option>
              <option value="Hardware">Hardware</option>
            </select>
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto max-h-[420px] overflow-y-auto">
          <table className="w-full text-sm">
            <thead className="opacity-70">
              <tr className="border-b">
                <th className="py-3 text-left font-medium">Team</th>
                <th className="py-3 text-left font-medium">Members</th>
                <th className="py-3 text-left font-medium">Track</th>
                <th className="py-3 text-left font-medium">Status</th>
                <th className="py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>

            <tbody>
              {filteredTeams.map((row) => (
                <tr key={row.id} className="border-b last:border-b-0">
                  <td className="py-3">{row.team}</td>
                  <td className="py-3">{row.members.length}</td>
                  <td className="py-3">{row.track}</td>
                  <td className="py-3">{statusChip(row.status)}</td>
                  <td className="py-3 text-right">
                    <div className="flex justify-end gap-2">
                      <button
                        onClick={() => setActiveTeamId(row.id)}
                        className="rounded-lg border border-[#FEA70A] bg-[#111435] px-3 py-1.5 text-xs hover:opacity-80"
                      >
                        View
                      </button>

                      <button
                        onClick={() => removeTeam(row.id)}
                        className="rounded-lg border border-red-400/30 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 hover:bg-red-500/20"
                      >
                        Remove
                      </button>
                    </div>
                  </td>
                </tr>
              ))}

              {filteredTeams.length === 0 && (
                <tr>
                  <td className="py-6 text-center opacity-70" colSpan={5}>
                    No matching teams.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal */}
      {activeTeam && (
        <div
          className={styles.modalBackdrop}
          onClick={() => setActiveTeamId(null)}
        >
          <div
            className={styles.card}
            onClick={(e) => e.stopPropagation()}
            style={{ maxWidth: 720, width: "100%" }}
          >
            <div className="flex items-start justify-between gap-3">
              <div>
                <div className="text-lg font-semibold">{activeTeam.team}</div>
                <div className="text-xs opacity-70">
                  {activeTeam.track} • {activeTeam.members.length} members
                </div>
                <div className="mt-2">{statusChip(activeTeam.status)}</div>
              </div>

              <button
                onClick={() => setActiveTeamId(null)}
                className="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs hover:bg-white/10"
              >
                Close
              </button>
            </div>

            <div className="mt-5">
              <div className="text-sm font-medium opacity-80">Members</div>

              <div className="mt-2 divide-y divide-white/10 rounded-xl border border-white/10">
                {activeTeam.members.map((m) => (
                  <div
                    key={m.id}
                    className="flex items-center justify-between gap-3 p-3"
                  >
                    <div>
                      <div className="text-sm font-medium">{m.name}</div>
                      <div className="text-xs opacity-60">{m.email}</div>
                    </div>

                    <button
                      onClick={() => removeMember(activeTeam.id, m.id)}
                      className="rounded-lg border border-red-400/30 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 hover:bg-red-500/20"
                    >
                      Remove
                    </button>
                  </div>
                ))}

                {activeTeam.members.length === 0 && (
                  <div className="p-4 text-sm opacity-70">No members left.</div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
