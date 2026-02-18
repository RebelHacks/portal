import { useState } from "react";
import style from "../../dashboard/dashboard.module.css";
import api from "@/lib/api";
import { useTeamContext } from "./TeamContext";
import type { CreateTeamRequest, Track, Team } from "@/lib/types";

const TEAM_NAME_MAX_LENGTH = 48;

export function TeamCreationDash() {
  const { refresh } = useTeamContext();
  const [teamName, setTeamName] = useState("");
  const [track, setTrack] = useState<Track>("Software");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);
  const [createdTeam, setCreatedTeam] = useState<Team | null>(null);

  const createTeam = async () => {
    const trimmedTeamName = teamName.trim();

    if (!trimmedTeamName) {
      setError("Team name is required");
      return;
    }

    if (trimmedTeamName.length > TEAM_NAME_MAX_LENGTH) {
      setError(`Team name must be ${TEAM_NAME_MAX_LENGTH} characters or fewer`);
      return;
    }

    setLoading(true);
    setError("");
    setSuccess(false);
    setCreatedTeam(null);

    const requestData: CreateTeamRequest = {
      teamName: trimmedTeamName,
      track,
    };

    try {
      const response = await api.post<Team>("/teams", requestData);
      setCreatedTeam(response);
      setSuccess(true);
      setTeamName("");
      setTrack("Software");
      await refresh();
    } catch (err: unknown) {
      const apiMessage =
        typeof err === "object" && err !== null && "response" in err
          ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
          : null;
      setError(apiMessage || "Failed to create team");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className={`${style.card} flex flex-col md:flex-row gap-10`}>
        <div className="flex-1">
          <h2 className={style.primaryTitle}>Create a Team</h2>
          <div className="space-y-4">
            <div className="flex flex-col gap-2">
              <label className="text-xs text-gray-500 tracking-wider">TEAM NAME</label>
              <input
                type="text"
                className={style.inputContainer}
                value={teamName}
                onChange={(e) => setTeamName(e.target.value)}
                placeholder="Enter team name..."
                disabled={loading}
                maxLength={TEAM_NAME_MAX_LENGTH}
              />
              <p className="text-xs text-(--sub-text)">Max {TEAM_NAME_MAX_LENGTH} characters</p>
            </div>
            <div className="flex flex-col gap-2">
              <label className="text-xs text-gray-500 tracking-wider">TRACK</label>
              <select
                className={style.dropdown}
                value={track}
                onChange={(e) => setTrack(e.target.value as Track)}
                disabled={loading}>
                <option value="Software">Software</option>
                <option value="Hardware">Hardware</option>
              </select>
            </div>
            {error && <p className="text-red-500 text-sm">{error}</p>}
            {success && createdTeam && (
              <div className="mt-4 p-4 bg-green-900/20 border border-green-500 rounded">
                <p className="text-green-500 text-sm font-semibold">Team created successfully!</p>
                <p className="text-white text-sm mt-2">
                  <strong>{createdTeam.teamName}</strong> - {createdTeam.track} Track
                </p>
                <p className="text-gray-400 text-xs">Status: {createdTeam.status}</p>
              </div>
            )}
            <button onClick={createTeam} disabled={loading} className={style.primaryButton}>
              {loading ? "Creating..." : "Create Team"}
            </button>
          </div>
        </div>

        <div className="flex-1 pt-10 border-t md:border-t-0 md:border-l border-(--primary-light-border) text-(--sub-text) md:pl-10">
          <h4 className={style.secondaryTitle}>Team Guidelines</h4>
          <ul className={`${style.list} space-y-2`}>
            <li>Names must be professional.</li>
            <li>Team name max is {TEAM_NAME_MAX_LENGTH} characters.</li>
            <li>Teams are limited to 5 members.</li>
            <li>Tracks must be either Software or Hardware.</li>
            <li>After creation, make a project.</li>
          </ul>
        </div>
      </div>
    </>
  );
}
