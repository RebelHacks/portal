import style from "../../dashboard/dashboard.module.css";
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
      isTeamCreated: false
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

  return (
    <>  
      {error && (
        <div className={style.modalBackdrop} style={{ zIndex: 70 }}>
          <div className={style.card}>
            <h2 className={style.secondaryTitle}>Attention!</h2>
            <p className="mb-6">{error}</p>
            <button onClick={() => setError(null)} className={style.primaryButton}>Got it</button>
          </div>
        </div>
      )}
      
      {showDisbandModal && (
        <div className={style.modalBackdrop} style={{ zIndex: 70 }}>
          <div className={style.card}>
            <h2 className={style.secondaryTitle}>Disband Team?</h2>
            <p className="mb-6">Are you sure you want to disband <span className={"text-[var(--primary)] font-bold"}>"{teamName}"</span>? This action cannot be undone.</p>
            <div className="flex gap-5">
              <button onClick={() => setShowDisbandModal(false)} className={style.secondaryButton}>Cancel</button>
              <button onClick={confirmDisband} className={style.warnButton}>Disband Now</button>
            </div>
          </div>
        </div>
      )}

      {!isTeamCreated ? (
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
                    onChange={(e) => setTeam(prev => ({ ...prev, teamName: e.target.value }))}
                    placeholder="Enter team name..."
                  />
                </div>
                <button onClick={handleCreateTeam} className={style.primaryButton}>Create Team</button>
              </div>
            </div>
            <div className="flex-1 pt-10 border-t md:border-t-0 md:border-l border-[var(--primary-light-border)] md:pl-10">
              <h4 className={style.secondaryTitle}>Team Guidelines</h4>
              <ul className={`${style.list} space-y-2`}>
                <li>Maximum of {teamLimit} members per team.</li>
                <li>Names must be professional.</li>
              </ul>
            </div>
          </div>
          <div className={style.card}>
            <h2 className={style.primaryTitle}>Invitations</h2>
            <p>You have no invitations at this time.</p>
          </div>
        </>
      ) : (
        <div className={`${style.card} space-y-8`}>
          <div className="flex flex-col md:flex-row justify-between items-start border-b border-[var(--primary-light-border)] pb-4 gap-4">
            <h1 className={style.primaryTitle}>{teamName}</h1>
            <div className="text-left md:text-right w-full md:w-auto">
              <div className="text-sm text-gray-400 mb-2">{currentTeam.length} / {teamLimit} Members</div>
              <button onClick={() => setShowDisbandModal(true)} className={`${style.warnButton} text-xs w-full md:w-auto`}>DISBAND TEAM</button>
            </div>
          </div>
      
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div>
              <h3 className={style.secondaryTitle}>Current Members</h3>
              <div className="space-y-3">
                {currentTeam.map((member) => (
                  <div key={member.name} className="flex justify-between items-center bg-white/5 p-3 rounded-lg">
                    <div className="flex items-center gap-3 min-w-0">
                      <div className={style.memberAvatar}>👤</div>
                      <span className="truncate max-w-[100px] md:max-w-none">{member.name}</span>
                      {member.name === "User" && <span className="text-[10px] bg-white/10 px-2 py-0.5 rounded text-gray-400 uppercase font-bold">Leader</span>}
                    </div>
                    {member.name !== "User" && <button onClick={() => removeMember(member.name)} className={`${style.warnButton} text-xs`}>Remove</button>}
                  </div>
                ))}
              </div>
            </div>
      
            <div>
              <h3 className={style.secondaryTitle}>Invite Members</h3>
              <input type="text" placeholder="Search users..." className={style.inputContainer} value={search} onChange={(e) => setSearch(e.target.value)} />
              <div className={`${style.primaryScroll} space-y-2 max-h-48 overflow-y-auto pr-2 mt-2`}>
                {filteredMembers.map((member) => (
                  <div key={member} className="flex justify-between items-center p-2 border-b border-white/5 hover:bg-white/5 rounded">
                    {member}
                    <button onClick={() => addMember(member)} className="text-[var(--primary)] cursor-pointer whitespace-nowrap">+ Invite</button>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}