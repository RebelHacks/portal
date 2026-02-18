"use client";

import { useEffect, useMemo, useState } from "react";
import api from "@/lib/api";
import type { Judge, Team, TeamStatus, Track, User } from "@/lib/types";
import styles from "../../dashboard-admin/admin.module.css";

type TeamStatusFilter = "All" | TeamStatus;
type TrackFilter = "All" | Track;
type MemberView = "all" | "team";

type Round = {
  id: string;
  name: string;
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

function norm(value: string): string {
  return value.trim().toLowerCase();
}

function normalizeTeam(team: Team): Team {
  return {
    ...team,
    track: team.track ?? "Software",
    project: team.project ?? { name: "", details: "" },
    assignments: team.assignments ?? {},
    members: team.members ?? [],
  };
}

export default function TeamsAdminPage() {
  const [teams, setTeams] = useState<Team[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [judges, setJudges] = useState<Judge[]>([]);
  const [loading, setLoading] = useState(true);
  const [savingTeamInfo, setSavingTeamInfo] = useState(false);
  const [savingProject, setSavingProject] = useState(false);
  const [savingAssignments, setSavingAssignments] = useState(false);
  const [savingMembers, setSavingMembers] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<TeamStatusFilter>("All");
  const [trackFilter, setTrackFilter] = useState<TrackFilter>("All");

  const [activeTeamId, setActiveTeamId] = useState<number | null>(null);
  const [draftTeamName, setDraftTeamName] = useState("");
  const [draftStatus, setDraftStatus] = useState<TeamStatus>("Unverified");
  const [draftTrack, setDraftTrack] = useState<Track>("Software");
  const [draftProjectName, setDraftProjectName] = useState("");
  const [draftProjectDetails, setDraftProjectDetails] = useState("");
  const [draftAssignments, setDraftAssignments] = useState<Record<string, number[]>>({});
  const [assignmentRound, setAssignmentRound] = useState<string>(ROUNDS[0].id);
  const [assignmentJudgeText, setAssignmentJudgeText] = useState<string>("");
  const [selectedMemberIds, setSelectedMemberIds] = useState<number[]>([]);
  const [memberSearch, setMemberSearch] = useState("");
  const [memberView, setMemberView] = useState<MemberView>("all");

  const activeTeam = useMemo(
    () => teams.find((team) => team.id === activeTeamId) ?? null,
    [teams, activeTeamId],
  );

  const filteredTeams = useMemo(() => {
    const query = norm(search);

    return teams
      .filter((team) => {
        if (!query) return true;
        const inTeam = norm(team.teamName).includes(query);
        const inTrack = norm(team.track).includes(query);
        const inProject =
          norm(team.project.name).includes(query) ||
          norm(team.project.details).includes(query);
        const inMembers = (team.members ?? []).some(
          (member) =>
            norm(member.name).includes(query) || norm(member.email).includes(query),
        );
        return inTeam || inTrack || inProject || inMembers;
      })
      .filter((team) => (statusFilter === "All" ? true : team.status === statusFilter))
      .filter((team) => (trackFilter === "All" ? true : team.track === trackFilter));
  }, [teams, search, statusFilter, trackFilter]);

  const memberTeamByUserId = useMemo(() => {
    const map = new Map<number, string>();
    teams.forEach((team) => {
      (team.members ?? []).forEach((member) => {
        map.set(member.id, team.teamName);
      });
    });
    return map;
  }, [teams]);

  const availableUsers = useMemo(() => users, [users]);

  const filteredAllUsers = useMemo(() => {
    const query = norm(memberSearch);
    if (!query) return availableUsers;

    return availableUsers.filter((user) => {
      const currentTeam = memberTeamByUserId.get(user.id) ?? "Unassigned";
      return (
        norm(user.name).includes(query) ||
        norm(user.email).includes(query) ||
        norm(currentTeam).includes(query)
      );
    });
  }, [availableUsers, memberSearch, memberTeamByUserId]);

  const filteredTeamUsers = useMemo(
    () => filteredAllUsers.filter((user) => selectedMemberIds.includes(user.id)),
    [filteredAllUsers, selectedMemberIds],
  );

  const visibleMemberUsers = useMemo(() => {
    const baseUsers = memberView === "team" ? filteredTeamUsers : filteredAllUsers;
    if (memberView === "all" && !norm(memberSearch)) {
      return baseUsers.slice(0, 3);
    }

    return baseUsers;
  }, [filteredAllUsers, filteredTeamUsers, memberSearch, memberView]);

  const displayRounds = useMemo(() => {
    const rounds = [...ROUNDS];
    const existingIds = new Set(rounds.map((round) => round.id));

    Object.keys(draftAssignments).forEach((roundId) => {
      if (!existingIds.has(roundId)) {
        rounds.push({ id: roundId, name: roundId });
      }
    });

    return rounds;
  }, [draftAssignments]);

  async function loadData() {
    setLoading(true);
    setError(null);

    try {
      const [teamResponse, userResponse, judgeResponse] = await Promise.all([
        api.get<Team[]>("/teams"),
        api.get<User[]>("/users"),
        api.get<Judge[]>("/judges"),
      ]);

      setTeams(teamResponse.map(normalizeTeam));
      setUsers(userResponse);
      setJudges(judgeResponse);
    } catch (err) {
      console.error(err);
      setError("Could not load teams right now.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadData();
  }, []);

  function openEditor(team: Team) {
    setActiveTeamId(team.id);
    setDraftTeamName(team.teamName);
    setDraftStatus(team.status);
    setDraftTrack(team.track);
    setDraftProjectName(team.project.name);
    setDraftProjectDetails(team.project.details);
    setDraftAssignments(team.assignments ?? {});
    setSelectedMemberIds((team.members ?? []).map((member) => member.id));
  }

  function closeEditor() {
    setActiveTeamId(null);
    setDraftTeamName("");
    setDraftStatus("Unverified");
    setDraftTrack("Software");
    setDraftProjectName("");
    setDraftProjectDetails("");
    setDraftAssignments({});
    setAssignmentRound(ROUNDS[0].id);
    setAssignmentJudgeText("");
    setSelectedMemberIds([]);
    setMemberSearch("");
    setMemberView("all");
  }

  function applyUpdatedTeam(updated: Team) {
    const normalized = normalizeTeam(updated);
    setTeams((current) =>
      current.map((team) => (team.id === normalized.id ? normalized : team)),
    );
    setDraftTeamName(normalized.teamName);
    setDraftStatus(normalized.status);
    setDraftTrack(normalized.track);
    setDraftProjectName(normalized.project.name);
    setDraftProjectDetails(normalized.project.details);
    setDraftAssignments(normalized.assignments);
    setSelectedMemberIds((normalized.members ?? []).map((member) => member.id));
  }

  async function saveTeamInfo() {
    if (!activeTeam) return;

    const teamName = draftTeamName.trim();
    if (!teamName) {
      setError("Team name cannot be empty.");
      return;
    }

    setSavingTeamInfo(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/teams/${activeTeam.id}`, {
        name: teamName,
        status: draftStatus,
        track: draftTrack,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not save team details.");
    } finally {
      setSavingTeamInfo(false);
    }
  }

  async function saveProjectInfo() {
    if (!activeTeam) return;

    setSavingProject(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/teams/${activeTeam.id}`, {
        projectName: draftProjectName,
        projectDetails: draftProjectDetails,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not save project details.");
    } finally {
      setSavingProject(false);
    }
  }

  function toggleMember(userId: number) {
    const isAlreadySelected = selectedMemberIds.includes(userId);

    if (!isAlreadySelected && selectedMemberIds.length >= 5) {
      setError("A team can only have up to 5 members.");
      return;
    }

    if (!isAlreadySelected) {
      setMemberView("team");
    }

    setSelectedMemberIds((current) =>
      current.includes(userId)
        ? current.filter((id) => id !== userId)
        : [...current, userId],
    );
  }

  async function saveMembers() {
    if (!activeTeam) return;
    if (selectedMemberIds.length > 5) {
      setError("A team can only have up to 5 members.");
      return;
    }

    setSavingMembers(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/teams/${activeTeam.id}/members`, {
        memberIds: selectedMemberIds,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not save team members.");
    } finally {
      setSavingMembers(false);
    }
  }

  function toggleJudge(roundId: string, judgeId: number) {
    setDraftAssignments((current) => {
      const selected = current[roundId] ?? [];
      const next = selected.includes(judgeId)
        ? selected.filter((id) => id !== judgeId)
        : [...selected, judgeId];
      return { ...current, [roundId]: next };
    });
  }

  function addJudgeToRound() {
    const query = norm(assignmentJudgeText);
    if (!query) {
      setError("Please enter a judge name.");
      return;
    }

    const judge = judges.find(
      (item) => norm(item.name) === query || norm(item.email) === query,
    );

    if (!judge) {
      setError("Judge not found. Use the judge name or email.");
      return;
    }

    setError(null);
    setDraftAssignments((current) => {
      const selected = current[assignmentRound] ?? [];
      if (selected.includes(judge.id)) {
        return current;
      }

      return {
        ...current,
        [assignmentRound]: [...selected, judge.id],
      };
    });
    setAssignmentJudgeText("");
  }

  async function saveAssignments() {
    if (!activeTeam) return;

    setSavingAssignments(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/teams/${activeTeam.id}`, {
        judgeAssignments: draftAssignments,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError("Could not save judge assignments.");
    } finally {
      setSavingAssignments(false);
    }
  }

  return (
    <div className={styles.card}>
      <div className="mb-4">
        <h2 className={styles.primaryTitle}>Teams</h2>
        <p className="mt-1 text-sm text-white/70">
          Edit team info, projects, members, and judge assignments.
        </p>
      </div>

      <div className={styles.card}>
        <div className="flex flex-col gap-3 md:flex-row md:items-center">
          <input
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            className="w-full rounded-xl border border-[#FEA70A]/60 bg-[#111435] px-3 py-2 text-sm outline-none md:max-w-sm"
            placeholder="Search team, project, member..."
          />

          <select
            value={statusFilter}
            onChange={(event) =>
              setStatusFilter(event.target.value as TeamStatusFilter)
            }
            className="rounded-xl border border-[#FEA70A]/60 bg-[#111435] px-3 py-2 text-sm outline-none"
          >
            <option value="All">All Status</option>
            <option value="Unverified">Unverified</option>
            <option value="Verified">Verified</option>
          </select>

          <select
            value={trackFilter}
            onChange={(event) => setTrackFilter(event.target.value as TrackFilter)}
            className="rounded-xl border border-[#FEA70A]/60 bg-[#111435] px-3 py-2 text-sm outline-none"
          >
            <option value="All">All Tracks</option>
            <option value="Software">Software</option>
            <option value="Hardware">Hardware</option>
          </select>

          <button
            onClick={() => void loadData()}
            className={`${styles.secondaryButton} text-xs`}
          >
            Refresh
          </button>

         
        </div>
      </div>

      {error && (
        <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200">
          {error}
        </div>
      )}

      <div className={`${styles.card} mt-4`}>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="opacity-70">
              <tr className="border-b border-white/10">
                <th className="py-3 text-left font-medium">Team</th>
                <th className="py-3 text-left font-medium">Track</th>
                <th className="py-3 text-left font-medium">Status</th>
                <th className="py-3 text-left font-medium">Project</th>
                <th className="py-3 text-left font-medium">Members</th>
                <th className="py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>

            <tbody>
              {!loading &&
                filteredTeams.map((team) => (
                  <tr key={team.id} className="border-b border-white/10 last:border-b-0">
                    <td className="py-3">
                      <div className="font-medium">{team.teamName}</div>
                    </td>
                    <td className="py-3">{team.track}</td>
                    <td className="py-3">
                      <span
                        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${
                          team.status === "Verified"
                            ? "border-green-400/30 bg-green-500/10 text-green-300"
                            : "border-white/10 bg-white/5 text-white/70"
                        }`}
                      >
                        {team.status}
                      </span>
                    </td>
                    <td className="py-3">{team.project.name || "—"}</td>
                    <td className="py-3">{(team.members ?? []).length}</td>
                    <td className="py-3 text-right">
                      <button
                        onClick={() => openEditor(team)}
                        className={`${styles.primaryButton} text-xs`}
                      >
                        Edit
                      </button>
                    </td>
                  </tr>
                ))}

              {loading && (
                <tr>
                  <td className="py-8 text-center text-white/70" colSpan={6}>
                    Loading teams...
                  </td>
                </tr>
              )}

              {!loading && filteredTeams.length === 0 && (
                <tr>
                  <td className="py-8 text-center text-white/70" colSpan={6}>
                    No matching teams.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {activeTeam && (
        <div
          className={`${styles.modalBackdrop} fixed inset-0 z-50 bg-black/50`}
          onMouseDown={closeEditor}
        >
          <div
            className={`${styles.cardModal} max-h-[90vh] w-[min(620px,95vw)] overflow-y-auto shadow-xl`}
            onMouseDown={(event) => event.stopPropagation()}
          >
            <div className="mb-4 flex items-start justify-between gap-4">
              <div>
                <div className="text-lg font-semibold">Edit Team</div>
                <div className="mt-1 text-sm text-white/70">{activeTeam.teamName}</div>
              </div>
              <button
                onClick={closeEditor}
                className="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs hover:opacity-80"
              >
                ✕
              </button>
            </div>

            <div className="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 text-sm font-semibold text-white/90">Team Info</div>
              <label className="text-xs text-white/70">Team Name</label>
              <input
                value={draftTeamName}
                onChange={(event) => setDraftTeamName(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full`}
                placeholder="Team name"
              />
              <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                  <label className="text-xs text-white/70">Track</label>
                  <select
                    value={draftTrack}
                    onChange={(event) => setDraftTrack(event.target.value as Track)}
                    className="mt-1 w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-white outline-none"
                  >
                    <option value="Software">Software</option>
                    <option value="Hardware">Hardware</option>
                  </select>
                </div>
                <div>
                  <label className="text-xs text-white/70">Status</label>
                  <select
                    value={draftStatus}
                    onChange={(event) => setDraftStatus(event.target.value as TeamStatus)}
                    className="mt-1 w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-white outline-none"
                  >
                    <option value="Unverified">Unverified</option>
                    <option value="Verified">Verified</option>
                  </select>
                </div>
              </div>
              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveTeamInfo()}
                  disabled={savingTeamInfo}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}
                >
                  {savingTeamInfo ? "Saving..." : "Save Team Info"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 text-sm font-semibold text-white/90">Project</div>
              <label className="text-xs text-white/70">Project Name</label>
              <input
                value={draftProjectName}
                onChange={(event) => setDraftProjectName(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full`}
                placeholder="Project name"
              />
              <label className="mt-3 block text-xs text-white/70">Project Details</label>
              <textarea
                value={draftProjectDetails}
                onChange={(event) => setDraftProjectDetails(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full resize-y`}
                rows={4}
                placeholder="Project description"
              />
              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveProjectInfo()}
                  disabled={savingProject}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}
                >
                  {savingProject ? "Saving..." : "Save Project"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 flex items-center justify-between">
                <div className="text-sm font-semibold text-white/90">Members</div>
                <div className="text-xs text-white/60">{selectedMemberIds.length}/5 selected</div>
              </div>

              <div className="mb-3 flex gap-2">
                <button
                  onClick={() => setMemberView("all")}
                  className={`text-xs ${styles.primaryButton} ${
                    memberView === "all"
                      ? "bg-(--primary-translucent)"
                      : "opacity-70"
                  }`}
                >
                  All Members ({filteredAllUsers.length})
                </button>
                <button
                  onClick={() => setMemberView("team")}
                  className={`text-xs ${styles.primaryButton} ${
                    memberView === "team"
                      ? "bg-(--primary-translucent)"
                      : "opacity-70"
                  }`}
                >
                  Team Members ({filteredTeamUsers.length})
                </button>
              </div>

              <input
                value={memberSearch}
                onChange={(event) => setMemberSearch(event.target.value)}
                className={`${styles.inputContainer} mb-3 w-full`}
                placeholder="Search member by name, email, or team..."
              />

              <div className="space-y-2">
                {memberView === "all" && !norm(memberSearch) && filteredAllUsers.length > 3 && (
                  <div className="rounded-xl border border-white/10 bg-white/5 p-3 text-xs text-white/60">
                    Showing first 3 members. Search by name to find more.
                  </div>
                )}

                {visibleMemberUsers.map((user) => (
                  <div
                    key={user.id}
                    className="flex items-start justify-between gap-3 rounded-xl border border-white/10 bg-white/5 p-3 hover:bg-white/10"
                  >
                    <div className="min-w-0">
                      <div className="truncate text-sm font-semibold">{user.name}</div>
                      <div className="truncate text-xs text-white/60">
                        {user.email} • {user.track} • Team:{" "}
                        {memberTeamByUserId.get(user.id) ?? "Unassigned"}
                      </div>
                    </div>
                    <button
                      onClick={() => toggleMember(user.id)}
                      className={`text-xs ${
                        selectedMemberIds.includes(user.id)
                          ? styles.warnButton
                          : styles.primaryButton
                      }`}
                    >
                      {selectedMemberIds.includes(user.id) ? "Remove" : "Add"}
                    </button>
                  </div>
                ))}

                {visibleMemberUsers.length === 0 && (
                  <div className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-white/70">
                    {memberView === "team"
                      ? "No members in team for this search."
                      : "No users match your search."}
                  </div>
                )}
              </div>
              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveMembers()}
                  disabled={savingMembers}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}
                >
                  {savingMembers ? "Saving..." : "Save Members"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-2 text-sm font-semibold text-white/90">Judge Assignments</div>
              <div className="mb-3 grid grid-cols-1 gap-2 md:grid-cols-3">
                <select
                  value={assignmentRound}
                  onChange={(event) => setAssignmentRound(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-white outline-none"
                >
                  {displayRounds.map((round) => (
                    <option key={round.id} value={round.id}>
                      {round.name}
                    </option>
                  ))}
                </select>
                <input
                  value={assignmentJudgeText}
                  onChange={(event) => setAssignmentJudgeText(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-white outline-none"
                  placeholder="Type judge name or email..."
                />
                <button
                  onClick={addJudgeToRound}
                  className={`${styles.primaryButton} text-xs whitespace-nowrap`}
                >
                  Add Judge To Round
                </button>
              </div>
              <div className="space-y-2">
                {displayRounds.map((round) => {
                  const selected = draftAssignments[round.id] ?? [];
                  return (
                    <details key={round.id} className="rounded-xl border border-white/10 bg-white/5">
                      <summary className="flex cursor-pointer items-center justify-between px-3 py-2 text-sm font-semibold hover:bg-white/10">
                        <span>{round.name}</span>
                        <span className="text-xs font-normal text-white/70">
                          {selected.length === 0
                            ? "No judge"
                            : selected.length === 1
                              ? "1 judge"
                              : `${selected.length} judges`}
                        </span>
                      </summary>
                      <div className="border-t border-white/10 px-3 py-2">
                        <div className="max-h-40 space-y-1 overflow-y-auto pr-1">
                          {judges.map((judge) => (
                            <label
                              key={judge.id}
                              className="flex cursor-pointer items-start gap-2 rounded-lg px-2 py-1 text-xs hover:bg-white/10"
                            >
                              <input
                                type="checkbox"
                                checked={selected.includes(judge.id)}
                                onChange={() => toggleJudge(round.id, judge.id)}
                                className="mt-0.5"
                              />
                              <span className="truncate font-medium">
                                {judge.name}
                                <span className="ml-1 font-normal text-white/60">
                                  ({judge.email})
                                </span>
                              </span>
                            </label>
                          ))}
                        </div>
                      </div>
                    </details>
                  );
                })}
              </div>

              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveAssignments()}
                  disabled={savingAssignments}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}
                >
                  {savingAssignments ? "Saving..." : "Save Assignments"}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
