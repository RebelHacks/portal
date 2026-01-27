"use client";
import style from "./dashboard.module.css";
import { useState } from "react";
import { redirect } from "next/navigation";
import { Submissions } from "../components/dashboard/Submissions";
import { Schedule } from "../components/dashboard/Schedule";

const debug = true;
const INITIAL_MEMBERS = ["Sally", "Alice", "Bob", "Kate", "Fred", "Alex", "Noah", "Billy"];
const TABS = ["Team", "Schedule", "Submissions"];
const TEAM_LIMIT = 5;

export default function RebelHackPage() {
  // !! IMPORTANT !!
  // Anything before this date will redirect to /redirect
  // If you want to test the dashboard comment the code below
  const now = new Date();
  if (now < new Date("2026-02-02T12:00:00-08:00") && !debug) {
    redirect('/redirect');
  }

  const [activeTab, setActiveTab] = useState("Team");
  const [teamName, setTeamName] = useState("");
  const [isTeamCreated, setIsTeamCreated] = useState(false);
  const [currentTeam, setCurrentTeam] = useState([{ name: "User" }]); 
  const [availableMembers, setAvailableMembers] = useState(INITIAL_MEMBERS);
  const [search, setSearch] = useState("");
  const [showDisbandModal, setShowDisbandModal] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // State for the Drawer
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  const handleCreateTeam = () => {
    if (!teamName.trim()) return setError("Please enter a team name.");
    setIsTeamCreated(true);
  };

  const confirmDisband = () => {
    const membersToReturn = currentTeam.filter(member => member.name !== "User").map(member => member.name);
    setAvailableMembers([...availableMembers, ...membersToReturn]); 
    setCurrentTeam([{ name: "User" }]); 
    setIsTeamCreated(false);
    setShowDisbandModal(false);
  };

  const addMember = (name: string) => {
    if (currentTeam.length >= TEAM_LIMIT) return setError(`Maximum capacity reached! A team can only have ${TEAM_LIMIT} members.`);
    setCurrentTeam([...currentTeam, { name }]); 
    setAvailableMembers(availableMembers.filter(member => member !== name)); 
  };

  const removeMember = (name: string) => {
    if (name === "User") return; 
    setCurrentTeam(currentTeam.filter(member => member.name !== name)); 
    setAvailableMembers([...availableMembers, name]); 
  };

  const filteredMembers = availableMembers.filter(member => member.toLowerCase().includes(search.toLowerCase()));


  return (
    <div className={`${style.pageContainer} min-h-screen flex flex-col relative`}>
      
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

      {isDrawerOpen && (
        <>
          <div 
            className={style.drawerBackdrop}
            onClick={() => setIsDrawerOpen(false)}
          />
          
          <aside className={style.drawerPanel}>
            
            <div className={style.drawerHeader}>
               <div className="text-xl font-bold tracking-widest text-[var(--primary)]">MENU</div>
               <button onClick={() => setIsDrawerOpen(false)} className={style.closeButton}>✕</button>
            </div>
            
            <nav className="space-y-2">
              {TABS.map((option) => (
                <div 
                  key={option} 
                  onClick={() => {
                    setActiveTab(option);
                    setIsDrawerOpen(false); 
                  }} 
                  className={`${style.option} ${activeTab === option ? style.active : ""} px-4 py-3 rounded text-lg`}
                >
                  {option}
                </div>
              ))}
            </nav>

          </aside>
        </>
      )}

      <header className={style.header}>
        <div className="flex items-center gap-4">
          <button 
            onClick={() => setIsDrawerOpen(true)}
            className={style.hamburgerButton}
          >
            <span className="text-xl leading-none">☰</span>
          </button>
          
          <h1 className="text-lg md:text-xl font-bold tracking-widest text-[var(--primary)] ml-2">
            REBEL HACKS
          </h1>
        </div>

        <div className="flex items-center gap-3">
           <div className={style.memberAvatar}>
             👤
           </div>
        </div>
      </header>

      <main className="flex-1 p-4 md:p-10">
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
                <div className="flex flex-col md:flex-row justify-between items-start border-b border-[var(--primary-light-border)] pb-4 gap-4">
                  <h1 className={style.primaryTitle}>{teamName}</h1>
                  <div className="text-left md:text-right w-full md:w-auto">
                    <div className="text-sm text-gray-400 mb-2">{currentTeam.length} / {TEAM_LIMIT} Members</div>
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
            )
          )}

          {activeTab === "Schedule" && (
            // <div className={style.card}>
            //   <h2 className={style.primaryTitle}>Event Schedule</h2>
              
            //   <h3 className="text-[var(--primary)] font-bold mt-6 mb-3 text-lg">Friday, February 20th</h3>
            //   <ul className="space-y-3 text-gray-300">
            //     <li>8:00 AM - 9:00 AM: Check In & Breakfast</li>
            //     <li>9:00 AM - 10:30 AM: Opening Ceremony</li>
            //     <li>10:30 AM - 11:00 AM: Team Lock-In & Hacking Begins</li>
            //     <li>2:00 PM - 3:00 PM: Lunch</li>
            //     <li>7:00 PM - 8:00 PM: Dinner</li>
            //     <li>9:30 PM: Hackers Exit UNLV</li>
            //   </ul>

            //   <h3 className="text-[var(--primary)] font-bold mt-8 mb-3 text-lg">Saturday, February 21st</h3>
            //   <ul className="space-y-3 text-gray-300">
            //     <li>8:00 AM - 9:00 AM: Breakfast</li>
            //     <li>11:00 AM: Project Submission</li>
            //     <li>12:00 PM - 1:00 PM: Lunch</li>
            //     <li>2:00 PM - 4:00 PM: Judging</li>
            //     <li>6:00 - 6:30 PM: Closing Ceremony</li>
            //   </ul>
            // </div>
            <Schedule />
          )}

          {activeTab === "Submissions" && (
            // <div className={style.card}>
            //   <h2 className={style.primaryTitle}>Project Submission</h2>
            //   <p className="text-gray-400 mt-4">Submission stuff goes here...</p>
            // </div>
            <Submissions />
          )}

        </div>
      </main>
    </div>
  );
}