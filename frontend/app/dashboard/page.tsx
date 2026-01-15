"use client";
import style from "./dashboard.module.css";
import { useState } from "react";

const INITIAL_MEMBERS = ["Sally", "Alice", "Bob", "Kate", "Fred", "Alex", "Noah", "Billy"];
const TABS = ["Team", "Schedule", "Submissions"];
const TEAM_LIMIT = 5;

export default function RebelHackPage() {
  const [activeTab, setActiveTab] = useState("Team");
  const [teamName, setTeamName] = useState("");
  const [isTeamCreated, setIsTeamCreated] = useState(false);
  // The string "User" represents the current logged in user.
  const [currentTeam, setCurrentTeam] = useState([{ name: "User" }]); 
  const [availableMembers, setAvailableMembers] = useState(INITIAL_MEMBERS);
  const [search, setSearch] = useState("");
  const [showDisbandModal, setShowDisbandModal] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleCreateTeam = () => {
    if (!teamName.trim()) return setError("Please enter a team name.");
    setIsTeamCreated(true);
  };

  const confirmDisband = () => {
    // Free up the rest of the team members and reset the team state
    const membersToReturn = currentTeam.filter(member => member.name !== "User").map(member => member.name);
    setAvailableMembers([...availableMembers, ...membersToReturn]); 
    setCurrentTeam([{ name: "User" }]); // Only the team leader remains
    setIsTeamCreated(false);
    setShowDisbandModal(false);
  };

  const addMember = (name: string) => {
    if (currentTeam.length >= TEAM_LIMIT) return setError(`Maximum capacity reached! A team can only have ${TEAM_LIMIT} members.`);
    setCurrentTeam([...currentTeam, { name }]); // Add new member as an object
    setAvailableMembers(availableMembers.filter(member => member !== name)); // Remove from available members
  };

  const removeMember = (name: string) => {
    if (name === "User") return; // Prevent removing team leader
    setCurrentTeam(currentTeam.filter(member => member.name !== name)); 
    setAvailableMembers([...availableMembers, name]); 
  };

  // Filter available members based on search input
  const filteredMembers = availableMembers.filter(member => member.toLowerCase().includes(search.toLowerCase()));

  return (
    <div className={style.pageContainer}>
      
      {error && (
        <div className={style.modalBackdrop}>
          <div className={style.card}>
            <h2 className={style.secondaryTitle}>Attention!</h2>
            <p className="mb-6">{error}</p>
            <button onClick={() => setError(null)} className={style.primaryButton}>Got it</button>
          </div>
        </div>
      )}
      
      {showDisbandModal && (
        <div className={style.modalBackdrop}>
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

      <div className="flex min-h-screen">
        <aside className={style.sidebar}>
          <div className="mb-10 text-2xl text-[var(--primary)] cursor-pointer">☰</div>
          <nav>
            {TABS.map((option) => (
              <div key={option} onClick={() => setActiveTab(option)} className={`${style.option} ${activeTab === option ? style.active : ""}`}>
                {option}
              </div>
            ))}
          </nav>
        </aside>

        <main className="flex-1 p-10">
          <header className="flex justify-between items-center mb-10">
            <h1 className="text-2xl font-bold tracking-widest text-[var(--primary)]">REBEL HACKS</h1>
            <div className="flex items-center gap-2"><div className={style.memberAvatar}>👤</div>User</div>
          </header>

          <div className="max-w-5xl mx-auto space-y-8">
            
            {activeTab === "Team" && (
              !isTeamCreated ? (
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
                            value={teamName} onChange={(e) => setTeamName(e.target.value)}
                            placeholder="Enter team name..."
                          />
                        </div>
                        <button onClick={handleCreateTeam} className={style.primaryButton}>Create Team</button>
                      </div>
                    </div>
                    <div className="flex-1 pt-10 border-t md:border-t-0 md:border-l border-[var(--primary-light-border)] md:pl-10">
                      <h4 className={style.secondaryTitle}>Team Guidelines</h4>
                      <ul className={`${style.list} space-y-2`}>
                        <li>Maximum of 5 members per team.</li>
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
                  <div className="flex justify-between items-start border-b border-[var(--primary-light-border)] pb-4">
                    <h1 className={style.primaryTitle}>{teamName}</h1>
                    <div className="text-right">
                      <div className="text-sm text-gray-400 mb-2">{currentTeam.length} / {TEAM_LIMIT} Members</div>
                      <button onClick={() => setShowDisbandModal(true)} className={`${style.warnButton} text-xs`}>DISBAND TEAM</button>
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
                              {member.name}
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
                            <button onClick={() => addMember(member)} className="text-[var(--primary)] cursor-pointer">+ Invite</button>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )
            )}

            {activeTab === "Schedule" && (
              <div className={style.card}>
                <h2 className={style.primaryTitle}>Event Schedule</h2>
                
                <h3 className="text-[var(--primary)] font-bold mt-6 mb-3 text-lg">Friday, February 20th</h3>
                <ul className="space-y-3 text-gray-300">
                  <li>8:00 AM - 9:00 AM: Check In & Breakfast</li>
                  <li>9:00 AM - 10:30 AM: Opening Ceremony</li>
                  <li>10:30 AM - 11:00 AM: Team Lock-In & Hacking Begins</li>
                  <li>2:00 PM - 3:00 PM: Lunch</li>
                  <li>7:00 PM - 8:00 PM: Dinner</li>
                  <li>9:30 PM: Hackers Exit UNLV</li>
                </ul>

                <h3 className="text-[var(--primary)] font-bold mt-8 mb-3 text-lg">Saturday, February 21st</h3>
                <ul className="space-y-3 text-gray-300">
                  <li>8:00 AM - 9:00 AM: Breakfast</li>
                  <li>11:00 AM: Project Submission</li>
                  <li>12:00 PM - 1:00 PM: Lunch</li>
                  <li>2:00 PM - 4:00 PM: Judging</li>
                  <li>6:00 - 6:30 PM: Closing Ceremony</li>
                </ul>
              </div>
            )}

            {activeTab === "Submissions" && (
              <div className={style.card}>
                <h2 className={style.primaryTitle}>Project Submission</h2>
                <p className="text-gray-400 mt-4">Submission stuff goes here...</p>
              </div>
            )}

          </div>
        </main>
      </div>
    </div>
  );
}