"use client";

import { useEffect, useMemo, useState } from "react";
import api from "@/lib/api";
import { CONFIGURED_ROUNDS, labelForRoundId } from "@/lib/rounds";
import type { Judge, Team, TeamStatus, Track, User } from "@/lib/types";
import styles from "../../dashboard-admin/admin.module.css";

type TeamStatusFilter = "All" | TeamStatus;
type TrackFilter = "All" | Track;

const TEAM_MEMBER_LIMIT = 5;
const TEAM_NAME_MAX_LENGTH = 48;
const PROJECT_DETAILS_MAX_LENGTH = 250;

function norm(value: string): string {
  return value.trim().toLowerCase();
}

function normalizeTeam(team: Team): Team {
  return {
    ...team,
    track: team.track ?? "Software",
    project: team.project ?? { name: "", details: "" },
    assignments: team.assignments ?? {},
    users: team.users ?? [],
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
  const [assignmentRound, setAssignmentRound] = useState<string>(CONFIGURED_ROUNDS[0].id);
  const [assignmentJudgeId, setAssignmentJudgeId] = useState<string>("");
  const [assignmentJudgeSearch, setAssignmentJudgeSearch] = useState<string>("");
  const [selectedMemberIds, setSelectedMemberIds] = useState<number[]>([]);
  const [memberSearch, setMemberSearch] = useState("");

  const activeTeam = useMemo(() => teams.find((team) => team.id === activeTeamId) ?? null, [teams, activeTeamId]);

  const filteredTeams = useMemo(() => {
    const query = norm(search);

    return teams
      .filter((team) => {
        if (!query) return true;
        const inTeam = norm(team.teamName).includes(query);
        const inTrack = norm(team.track).includes(query);
        const inProject = norm(team.project.name).includes(query) || norm(team.project.details).includes(query);
        const inMembers = (team.users ?? []).some(
          (member) => norm(member.name).includes(query) || norm(member.email).includes(query),
        );
        return inTeam || inTrack || inProject || inMembers;
      })
      .filter((team) => (statusFilter === "All" ? true : team.status === statusFilter))
      .filter((team) => (trackFilter === "All" ? true : team.track === trackFilter));
  }, [teams, search, statusFilter, trackFilter]);

  const memberTeamByUserId = useMemo(() => {
    const map = new Map<number, string>();
    teams.forEach((team) => {
      (team.users ?? []).forEach((member) => {
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
      return norm(user.name).includes(query) || norm(user.email).includes(query) || norm(currentTeam).includes(query);
    });
  }, [availableUsers, memberSearch, memberTeamByUserId]);

  const filteredTeamUsers = useMemo(
    () => filteredAllUsers.filter((user) => selectedMemberIds.includes(user.id)),
    [filteredAllUsers, selectedMemberIds],
  );

  const activeLeader = useMemo(() => {
    if (!activeTeam?.leaderId) return null;
    return (activeTeam.users ?? []).find((member) => member.id === activeTeam.leaderId) ?? null;
  }, [activeTeam]);

  const filteredNonTeamUsers = useMemo(
    () => filteredAllUsers.filter((user) => !memberTeamByUserId.has(user.id)),
    [filteredAllUsers, memberTeamByUserId],
  );

  const sortedJudges = useMemo(() => [...judges].sort((a, b) => a.name.localeCompare(b.name)), [judges]);
  const filteredJudges = useMemo(() => {
    const query = norm(assignmentJudgeSearch);
    if (!query) return sortedJudges;
    return sortedJudges.filter((judge) => norm(judge.name).includes(query) || norm(judge.email).includes(query));
  }, [sortedJudges, assignmentJudgeSearch]);

  const judgesById = useMemo(() => {
    const map = new Map<number, Judge>();
    judges.forEach((judge) => {
      map.set(judge.id, judge);
    });
    return map;
  }, [judges]);

  const displayRounds = useMemo(() => {
    const rounds = [...CONFIGURED_ROUNDS];
    const existingIds = new Set(rounds.map((round) => round.id));

    Object.keys(draftAssignments).forEach((roundId) => {
      if (!existingIds.has(roundId)) {
        rounds.push({ id: roundId, name: labelForRoundId(roundId) });
      }
    });

    return rounds;
  }, [draftAssignments]);

  const currentAssignments = useMemo(() => {
    const items: Array<{ roundId: string; roundLabel: string; judgeId: number; judgeName: string; judgeEmail: string }> =
      [];

    Object.entries(draftAssignments).forEach(([roundId, judgeIds]) => {
      judgeIds.forEach((judgeId) => {
        const judge = judgesById.get(judgeId);
        items.push({
          roundId,
          roundLabel: labelForRoundId(roundId),
          judgeId,
          judgeName: judge?.name ?? `Judge ${judgeId}`,
          judgeEmail: judge?.email ?? "Unknown email",
        });
      });
    });

    return items.sort((a, b) => {
      if (a.roundId !== b.roundId) return a.roundId.localeCompare(b.roundId);
      return a.judgeName.localeCompare(b.judgeName);
    });
  }, [draftAssignments, judgesById]);

  async function loadData() {
    setLoading(true);
    setError(null);

    try {
      const [teamResponse, userResponse, judgeResponse] = await Promise.all([
        api.get<Team[]>("/admin/teams"),
        api.get<User[]>("/admin/users"),
        api.get<Judge[]>("/admin/judges"),
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
    setAssignmentRound(CONFIGURED_ROUNDS[0].id);
    setAssignmentJudgeId("");
    setAssignmentJudgeSearch("");
    setSelectedMemberIds((team.users ?? []).map((member) => member.id));
  }

  function closeEditor() {
    setActiveTeamId(null);
    setDraftTeamName("");
    setDraftStatus("Unverified");
    setDraftTrack("Software");
    setDraftProjectName("");
    setDraftProjectDetails("");
    setDraftAssignments({});
    setAssignmentRound(CONFIGURED_ROUNDS[0].id);
    setAssignmentJudgeId("");
    setAssignmentJudgeSearch("");
    setSelectedMemberIds([]);
    setMemberSearch("");
  }

  function applyUpdatedTeam(updated: Team) {
    const normalized = normalizeTeam(updated);
    setTeams((current) => current.map((team) => (team.id === normalized.id ? normalized : team)));
    setDraftTeamName(normalized.teamName);
    setDraftStatus(normalized.status);
    setDraftTrack(normalized.track);
    setDraftProjectName(normalized.project.name);
    setDraftProjectDetails(normalized.project.details);
    setDraftAssignments(normalized.assignments);
    setSelectedMemberIds((normalized.users ?? []).map((member) => member.id));
  }

  async function saveTeamInfo() {
    if (!activeTeam) return;

    const teamName = draftTeamName.trim();
    if (!teamName) {
      setError("Team name cannot be empty.");
      return;
    }
    if (teamName.length > TEAM_NAME_MAX_LENGTH) {
      setError(`Team name must be ${TEAM_NAME_MAX_LENGTH} characters or fewer.`);
      return;
    }

    setSavingTeamInfo(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/admin/teams/${activeTeam.id}`, {
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
      const updated = await api.patch<Team>(`/admin/teams/${activeTeam.id}`, {
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

  async function updateMembers(memberIds: number[]) {
    if (!activeTeam) return;
    if (memberIds.length > TEAM_MEMBER_LIMIT) {
      setError(`A team can only have up to ${TEAM_MEMBER_LIMIT} members.`);
      return;
    }

    setSavingMembers(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/admin/teams/${activeTeam.id}/users`, {
       'userIds' : memberIds,
      });
      applyUpdatedTeam(updated);
    } catch (err: unknown) {
      console.error(err);
      const apiMessage =
        typeof err === "object" && err !== null && "response" in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
          : null;
      setError(apiMessage ?? "Could not update team members.");
    } finally {
      setSavingMembers(false);
    }
  }

  function onAddMember(userId: number) {
    if (savingMembers || selectedMemberIds.includes(userId)) return;
    void updateMembers([...selectedMemberIds, userId]);
  }

  function onRemoveMember(userId: number) {
    if (savingMembers || !selectedMemberIds.includes(userId)) return;
    void updateMembers(selectedMemberIds.filter((id) => id !== userId));
  }

  async function persistAssignments(
    nextAssignments: Record<string, number[]>,
    failureMessage: string,
  ) {
    if (!activeTeam) return;

    setSavingAssignments(true);
    setError(null);
    try {
      const updated = await api.patch<Team>(`/admin/teams/${activeTeam.id}`, {
        judgeAssignments: nextAssignments,
      });
      applyUpdatedTeam(updated);
    } catch (err) {
      console.error(err);
      setError(failureMessage);
    } finally {
      setSavingAssignments(false);
    }
  }

  async function addJudgeToRound() {
    const judgeId = Number(assignmentJudgeId);
    if (!judgeId) {
      setError("Please select a judge.");
      return;
    }

    const selected = draftAssignments[assignmentRound] ?? [];
    if (selected.includes(judgeId)) {
      setError("This judge is already assigned to that round.");
      return;
    }

    const nextAssignments = {
      ...draftAssignments,
      [assignmentRound]: [...selected, judgeId],
    };

    await persistAssignments(nextAssignments, "Could not add judge assignment.");
  }

  async function removeJudgeFromRound(roundId: string, judgeId: number) {
    const selected = draftAssignments[roundId] ?? [];
    if (!selected.includes(judgeId)) {
      return;
    }

    const nextSelected = selected.filter((id) => id !== judgeId);
    const nextAssignments = { ...draftAssignments };
    if (nextSelected.length === 0) {
      delete nextAssignments[roundId];
    } else {
      nextAssignments[roundId] = nextSelected;
    }

    await persistAssignments(nextAssignments, "Could not remove judge assignment.");
  }

  function onAssignmentRoundChange(roundId: string) {
    setAssignmentRound(roundId);
  }

  useEffect(() => {
    const hasSelectedRound = displayRounds.some((round) => round.id === assignmentRound);
    if (!hasSelectedRound && displayRounds.length > 0) {
      setAssignmentRound(displayRounds[0].id);
    }
  }, [assignmentRound, displayRounds]);

  useEffect(() => {
    if (filteredJudges.length === 0) {
      if (assignmentJudgeId !== "") {
        setAssignmentJudgeId("");
      }
      return;
    }

    const hasSelectedJudge = filteredJudges.some((judge) => String(judge.id) === assignmentJudgeId);
    if (!hasSelectedJudge) {
      setAssignmentJudgeId(String(filteredJudges[0].id));
    }
  }, [assignmentJudgeId, filteredJudges]);

  return (
    <div className={`${styles.card} text-(--sub-text)`}>
      <div className="mb-4">
        <h2 className={styles.primaryTitle}>Teams</h2>
        <p className="mt-1 text-sm text-(--sub-text)">Edit team info, projects, members, and judge assignments.</p>
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
            onChange={(event) => setStatusFilter(event.target.value as TeamStatusFilter)}
            className="rounded-xl border border-[#FEA70A]/60 bg-[#111435] px-3 py-2 text-sm outline-none">
            <option value="All">All Status</option>
            <option value="Unverified">Unverified</option>
            <option value="Verified">Verified</option>
          </select>

          <select
            value={trackFilter}
            onChange={(event) => setTrackFilter(event.target.value as TrackFilter)}
            className="rounded-xl border border-[#FEA70A]/60 bg-[#111435] px-3 py-2 text-sm outline-none">
            <option value="All">All Tracks</option>
            <option value="Software">Software</option>
            <option value="Hardware">Hardware</option>
          </select>

          <button onClick={() => void loadData()} className={`${styles.secondaryButton} text-xs`}>
            Refresh
          </button>
        </div>
      </div>

      {error && (
        <div className="mt-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-(--sub-text)">
          {error}
        </div>
      )}

      <div className={`${styles.card} mt-4`}>
        <div className="overflow-x-auto h-100">
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
                            ? "border-green-400/30 bg-green-500/10 text-(--sub-text)"
                            : "border-white/10 bg-white/5 text-(--sub-text)"
                        }`}>
                        {team.status}
                      </span>
                    </td>
                    <td className="py-3">{team.project.name || "—"}</td>
                    <td className="py-3">{(team.users ?? []).length}</td>
                    <td className="py-3 text-right">
                      <button onClick={() => openEditor(team)} className={`${styles.primaryButton} text-xs`}>
                        Edit
                      </button>
                    </td>
                  </tr>
                ))}

              {loading && (
                <tr>
                  <td className="py-8 text-center text-(--sub-text)" colSpan={6}>
                    Loading teams...
                  </td>
                </tr>
              )}

              {!loading && filteredTeams.length === 0 && (
                <tr>
                  <td className="py-8 text-center text-(--sub-text)" colSpan={6}>
                    No matching teams.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {activeTeam && (
        <div className={`${styles.modalBackdrop} fixed inset-0 z-50 bg-black/50`} onMouseDown={closeEditor}>
          <div
            className={`${styles.cardModal} max-h-[90vh] w-[min(620px,95vw)] overflow-y-auto shadow-xl`}
            onMouseDown={(event) => event.stopPropagation()}>
            <div className="mb-4 flex items-start justify-between gap-4">
              <div>
                <div className="text-lg font-semibold">Edit Team</div>
                <div className="mt-1 text-sm text-(--sub-text)">{activeTeam.teamName}</div>
              </div>
              <button
                onClick={closeEditor}
                className="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs hover:opacity-80">
                ✕
              </button>
            </div>

            <div className="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 text-sm font-semibold text-(--sub-text)">Team Info</div>
              <label className="text-xs text-(--sub-text)">Team Name</label>
              <input
                value={draftTeamName}
                onChange={(event) => setDraftTeamName(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full`}
                placeholder="Team name"
                maxLength={TEAM_NAME_MAX_LENGTH}
              />
              <div className="mt-1 text-xs text-(--sub-text)">Max {TEAM_NAME_MAX_LENGTH} characters</div>
              <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                  <label className="text-xs text-(--sub-text)">Track</label>
                  <select
                    value={draftTrack}
                    onChange={(event) => setDraftTrack(event.target.value as Track)}
                    className="mt-1 w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                    <option value="Software">Software</option>
                    <option value="Hardware">Hardware</option>
                  </select>
                </div>
                <div>
                  <label className="text-xs text-(--sub-text)">Status</label>
                  <select
                    value={draftStatus}
                    onChange={(event) => setDraftStatus(event.target.value as TeamStatus)}
                    className="mt-1 w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                    <option value="Unverified">Unverified</option>
                    <option value="Verified">Verified</option>
                  </select>
                </div>
              </div>
              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveTeamInfo()}
                  disabled={savingTeamInfo}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                  {savingTeamInfo ? "Saving..." : "Save Team Info"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 text-sm font-semibold text-(--sub-text)">Project</div>
              <label className="text-xs text-(--sub-text)">Project Name</label>
              <input
                value={draftProjectName}
                onChange={(event) => setDraftProjectName(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full`}
                placeholder="Project name"
              />
              <label className="mt-3 block text-xs text-(--sub-text)">Project Details</label>
              <textarea
                value={draftProjectDetails}
                onChange={(event) => setDraftProjectDetails(event.target.value)}
                className={`${styles.inputContainer} mt-1 mb-0 w-full resize-y`}
                rows={4}
                placeholder="Project description"
                maxLength={PROJECT_DETAILS_MAX_LENGTH}
              />
              <div className="mt-1 text-xs text-(--sub-text)">Max {PROJECT_DETAILS_MAX_LENGTH} characters</div>
              <div className="mt-3 flex justify-end">
                <button
                  onClick={() => void saveProjectInfo()}
                  disabled={savingProject}
                  className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                  {savingProject ? "Saving..." : "Save Project"}
                </button>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-3 flex items-center justify-between">
                <div className="text-sm font-semibold text-(--sub-text)">Members</div>
                <div className="text-xs text-(--sub-text)">
                  {selectedMemberIds.length}/{TEAM_MEMBER_LIMIT} in team
                  {savingMembers ? " • Updating..." : ""}
                </div>
              </div>

              <div className="mb-3 text-xs text-(--sub-text)">
                Leader: {activeLeader ? activeLeader.name || activeLeader.email : "None"}
              </div>

              <input
                value={memberSearch}
                onChange={(event) => setMemberSearch(event.target.value)}
                className={`${styles.inputContainer} mb-3 w-full`}
                placeholder="Search member by name, email, or team..."
              />

              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div className="rounded-xl border border-white/10 bg-white/5 p-3">
                  <div className="mb-2 text-xs font-semibold text-(--sub-text)">
                    All Members ({filteredNonTeamUsers.length})
                  </div>
                  <div className="max-h-64 space-y-2 overflow-y-auto pr-1">
                    {filteredNonTeamUsers.map((user) => {
                      const currentTeam = memberTeamByUserId.get(user.id);
                      const canAdd = !currentTeam || (activeTeam != null && currentTeam === activeTeam.teamName);

                      return (
                        <div
                          key={`all-${user.id}`}
                          className="flex items-start justify-between gap-3 rounded-xl border border-white/10 bg-black/10 p-3">
                          <div className="min-w-0">
                            <div className="truncate text-sm font-semibold">{user.name}</div>
                            <div className="truncate text-xs text-(--sub-text)">
                              {user.email} • {user.track} • Team: {currentTeam ?? "Unassigned"}
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={() => onAddMember(user.id)}
                            disabled={savingMembers || selectedMemberIds.length >= TEAM_MEMBER_LIMIT || !canAdd}
                            className={`${styles.primaryButton} text-xs disabled:cursor-not-allowed disabled:opacity-60`}>
                            {canAdd ? "Add" : "On Another Team"}
                          </button>
                        </div>
                      );
                    })}

                    {filteredNonTeamUsers.length === 0 && (
                      <div className="rounded-xl border border-white/10 bg-black/10 p-3 text-sm text-(--sub-text)">
                        No users match your search.
                      </div>
                    )}
                  </div>
                </div>

                <div className="rounded-xl border border-white/10 bg-white/5 p-3">
                  <div className="mb-2 text-xs font-semibold text-(--sub-text)">
                    Team Members ({filteredTeamUsers.length})
                  </div>
                  <div className="max-h-64 space-y-2 overflow-y-auto pr-1">
                    {filteredTeamUsers.map((user) => (
                      <div
                        key={`team-${user.id}`}
                        className="flex items-start justify-between gap-3 rounded-xl border border-white/10 bg-black/10 p-3">
                        <div className="min-w-0">
                          <div className="flex items-center gap-2">
                            <div className="truncate text-sm font-semibold">{user.name}</div>
                            {activeTeam?.leaderId === user.id && (
                              <span className="rounded-full border border-[#FEA70A]/40 bg-[#FEA70A]/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-(--sub-text)">
                                Leader
                              </span>
                            )}
                          </div>
                          <div className="truncate text-xs text-(--sub-text)">
                            {user.email} • {user.track}
                          </div>
                        </div>
                        <button
                          type="button"
                          className={`${styles.warnButton} text-xs`}
                          onClick={() => onRemoveMember(user.id)}
                          disabled={savingMembers}>
                          Remove
                        </button>
                      </div>
                    ))}

                    {filteredTeamUsers.length === 0 && (
                      <div className="rounded-xl border border-white/10 bg-black/10 p-3 text-sm text-(--sub-text)">
                        No team members for this search.
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-2 text-sm font-semibold text-(--sub-text)">Add Assignment</div>
              <div className="grid grid-cols-1 gap-2 md:grid-cols-3">
                <select
                  value={assignmentRound}
                  onChange={(event) => onAssignmentRoundChange(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                  {displayRounds.map((round) => (
                    <option key={round.id} value={round.id}>
                      {round.name}
                    </option>
                  ))}
                </select>
                <input
                  value={assignmentJudgeSearch}
                  onChange={(event) => setAssignmentJudgeSearch(event.target.value)}
                  className="w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none"
                  placeholder="Search judges..."
                />
                <button
                  onClick={() => void addJudgeToRound()}
                  disabled={savingAssignments || filteredJudges.length === 0}
                  className={`${styles.primaryButton} text-xs whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-60`}>
                  {savingAssignments ? "Saving..." : "Add Judge To Round"}
                </button>
              </div>
              <div className="mt-2">
                <select
                  value={assignmentJudgeId}
                  onChange={(event) => setAssignmentJudgeId(event.target.value)}
                  size={Math.min(12, Math.max(6, filteredJudges.length || 6))}
                  className="h-52 w-full rounded-xl border border-white/10 bg-[#111435] px-3 py-2 text-sm text-(--sub-text) outline-none">
                  {filteredJudges.map((judge) => (
                    <option key={judge.id} value={judge.id}>
                      {judge.name} ({judge.email})
                    </option>
                  ))}
                </select>
                <div className="mt-1 text-xs text-(--sub-text)">
                  Showing {filteredJudges.length} judge{filteredJudges.length === 1 ? "" : "s"}
                </div>
              </div>
            </div>

            <div className="mt-4 rounded-2xl border border-white/10 bg-black/10 p-4">
              <div className="mb-2 text-sm font-semibold text-(--sub-text)">Current Assignments</div>
              {currentAssignments.length === 0 ? (
                <div className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-(--sub-text)">
                  No assignments yet.
                </div>
              ) : (
                <div className="space-y-2">
                  {currentAssignments.map((item) => (
                    <div
                      key={`${item.roundId}-${item.judgeId}`}
                      className="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 p-3 text-sm">
                      <span>
                        {item.roundLabel} - {item.judgeName} ({item.judgeEmail})
                      </span>
                      <button
                        onClick={() => void removeJudgeFromRound(item.roundId, item.judgeId)}
                        disabled={savingAssignments}
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
    </div>
  );
}
