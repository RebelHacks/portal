import style from "../../dashboard/dashboard.module.css";
import { TeamViewDash } from "./TeamViewDash";
import { TeamCreationDash } from "./TeamCreationDash";
import { TeamModals } from "./TeamModals";
import { useState } from "react";

export interface TeamData {
  teamName: string;
  isTeamCreated: boolean;
  currentTeam: { name: string }[];
  availableMembers: string[];
  teamLimit: number;
}

interface TeamProps {
  teamData: TeamData;
  setTeam: (updater: (prev: TeamData) => TeamData) => void;
}

export function Team({ teamData, setTeam }: TeamProps) {
  const { teamName, isTeamCreated, currentTeam, availableMembers, teamLimit } = teamData;
  const [search, setSearch] = useState("");
  const [showDisbandModal, setShowDisbandModal] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleCreateTeam = () => {
    if (!teamName.trim()) return setError("Please enter a team name.");
    setTeam(prev => ({ ...prev, isTeamCreated: true }));
  };

  const confirmDisband = () => {
    const membersToReturn = currentTeam.filter(member => member.name !== "User").map(member => member.name);
    setTeam(prev => ({
      ...prev,
      availableMembers: [...prev.availableMembers, ...membersToReturn],
      currentTeam: [{ name: "User" }],
      isTeamCreated: false,
      teamName: ""
    }));
    setShowDisbandModal(false);
  };

  const addMember = (name: string) => {
    if (currentTeam.length >= teamLimit) return setError(`Maximum capacity reached! A team can only have ${teamLimit} members.`);
    setTeam(prev => ({
      ...prev,
      currentTeam: [...prev.currentTeam, { name }],
      availableMembers: prev.availableMembers.filter(member => member !== name)
    }));
  };

  const removeMember = (name: string) => {
    if (name === "User") return;  // Prevent removing the team leader
    setTeam(prev => ({
      ...prev,
      currentTeam: prev.currentTeam.filter(member => member.name !== name),
      availableMembers: [...prev.availableMembers, name]
    }));
  };

  const filteredMembers = availableMembers.filter(member => member.toLowerCase().includes(search.toLowerCase()));
  const handleTeamAction = (type: "add" | "remove" | "disband", payload?: string) => {
    if (type === "disband") {
      setShowDisbandModal(true);
    } else if (type === "add" && payload) {
      addMember(payload);
    } else if (type === "remove" && payload) {
      removeMember(payload);
    }
  };

  const handleCreationAction = (action: "updateName" | "create", value?: string) => {
    if (action === "updateName" && value !== undefined) {
      setTeam(prev => ({ ...prev, teamName: value }));
    }
    if (action === "create") {
      handleCreateTeam();
    }
  };

  const handleModalAction = (action: "closeError" | "cancelDisband" | "confirmDisband") => {
    if (action === "closeError") setError(null);
    if (action === "cancelDisband") setShowDisbandModal(false);
    if (action === "confirmDisband") confirmDisband();
  };
  
  return (
    <>  
      <TeamModals state={{ error, showDisband: showDisbandModal, teamName: teamData.teamName }} onAction={handleModalAction} />
      
      {isTeamCreated ? (
        <TeamViewDash teamData={teamData} onAction={handleTeamAction} />
      ) : (
        <>
          <TeamCreationDash teamData={teamData} onAction={handleCreationAction} />

          <div className={style.card}>
            <h2 className={style.primaryTitle}>Invitations</h2>
            <p>You have no invitations at this time.</p>
          </div>
        </>
      )}
    </>
  );
}