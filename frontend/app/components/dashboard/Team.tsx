import { TeamViewDash } from "./TeamViewDash";
import { TeamCreationDash } from "./TeamCreationDash";
import { TeamModals } from "./TeamModals";
import { TeamInvitationsDash } from "./TeamInvitationDash";
import { useState } from "react";

const INITIAL_MEMBERS = ["Sally", "Alice", "Bob", "Kate", "Fred", "Alex", "Noah", "Billy"];
const TEAM_LIMIT = 5;
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
  
  const handleTeamAction = ( type: "add" | "remove" | "disband" | "leave", payload?: string) => {
    if (type === "disband") {
      setShowDisbandModal(true);
    } 
    else if (type === "leave") {
      setTeam(prev => ({
        ...prev,
        isTeamCreated: false,
        currentTeam: [{ name: "User" }],
        teamName: ""
      }));
    }
    else if (type === "add" && payload) {
      addMember(payload);
    } 
    else if (type === "remove" && payload) {
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
  const [invitations, setInvitations] = useState<TeamData[]>([
    {
      teamName: "Code Warriors",
      isTeamCreated: true,
      currentTeam: [{ name: "Alex" }, { name: "Sam" }],
      availableMembers: INITIAL_MEMBERS,
      teamLimit: TEAM_LIMIT
    },
    {
      teamName: "Debuggers United",
      isTeamCreated: true,
      currentTeam: [{ name: "Lily" }, { name: "Mark" }, { name: "Nina" }],
      availableMembers: INITIAL_MEMBERS,
      teamLimit: TEAM_LIMIT
    },
    {
      teamName: "Script Kiddies",
      isTeamCreated: true,
      currentTeam: [{ name: "Eva" }],
      availableMembers: INITIAL_MEMBERS,
      teamLimit: TEAM_LIMIT
    }
  ]);

  const handleInviteAction = (action: "accept" | "decline", team: TeamData) => {
    if (action === "accept") {
      const teamWithUser: TeamData = {
        ...team,
        currentTeam: [...team.currentTeam, { name: "User" }]
      };
      setTeam(() => teamWithUser);
      setInvitations((prev) => prev.filter((t) => t.teamName !== team.teamName));
    } else {
      setInvitations((prev) => prev.filter((t) => t.teamName !== team.teamName));
    }
  };
  
  return (
    <>  
      <TeamModals state={{ error, showDisband: showDisbandModal, teamName: teamData.teamName }} onAction={handleModalAction} />
      
      {isTeamCreated ? (
        <TeamViewDash teamData={teamData} onAction={handleTeamAction} />
      ) : (
        <>
          <TeamCreationDash teamData={teamData} onAction={handleCreationAction} />

          <TeamInvitationsDash invitations={invitations} onAction={handleInviteAction} />
        </>
      )}
    </>
  );
}