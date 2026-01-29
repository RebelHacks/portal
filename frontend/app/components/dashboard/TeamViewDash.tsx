import { useState } from "react";
import style from "../../dashboard/dashboard.module.css";
import type { TeamData } from "./Team"; 

interface TeamViewDashProps {
  teamData: TeamData;
  onAction: (type: "add" | "remove" | "disband" | "leave", payload?: string) => void;
}

export function TeamViewDash({ teamData, onAction }: TeamViewDashProps) {
  const [search, setSearch] = useState("");
  
  const { teamName, currentTeam, availableMembers, teamLimit } = teamData;

  const filteredMembers = availableMembers.filter((member) =>
    member.toLowerCase().includes(search.toLowerCase())
  );

  // User is leader if they are the first member in the currentTeam array
  const isLeader = currentTeam.length > 0 && currentTeam[0].name === "User";

  return (
    <div className={`${style.card} space-y-8`}>
      <div className="flex flex-col md:flex-row justify-between items-start border-b border-[var(--primary-light-border)] pb-4 gap-4">
        <h1 className={style.primaryTitle}>{teamName}</h1>
        <div className="text-left md:text-right w-full md:w-auto">
          <div className="text-sm text-gray-400 mb-2">
            {currentTeam.length} / {teamLimit} Members
          </div>
          
          {isLeader ? (
            <button 
              onClick={() => onAction("disband")} 
              className={`${style.warnButton} text-xs w-full md:w-auto`}
            >
              DISBAND TEAM
            </button>
          ) : (
            <button 
              onClick={() => onAction("leave")} 
              className={`${style.warnButton} text-xs w-full md:w-auto`}
            >
              LEAVE TEAM
            </button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        <div className={!isLeader ? "lg:col-span-2" : ""}>
          <h3 className={style.secondaryTitle}>Current Members</h3>
          <div className="space-y-3">
            {currentTeam.map((member, index) => (
              <div key={member.name} className="flex justify-between items-center bg-white/5 p-3 rounded-lg">
                <div className="flex items-center gap-3 min-w-0">
                  <div className={style.memberAvatar}>👤</div>
                  <span className="truncate max-w-[100px] md:max-w-none">{member.name}</span>
                  
                  {index === 0 && (
                    <span className="text-[10px] bg-white/10 px-2 py-0.5 rounded text-gray-400 uppercase font-bold">
                      Leader
                    </span>
                  )}
                </div>

                {isLeader && index !== 0 && (
                  <button 
                    onClick={() => onAction("remove", member.name)} 
                    className={`${style.warnButton} text-xs`}
                  >
                    Remove
                  </button>
                )}
              </div>
            ))}
          </div>
        </div>

        {isLeader && (
          <div>
            <h3 className={style.secondaryTitle}>Invite Members</h3>
            <input 
              type="text" 
              placeholder="Search users..." 
              className={style.inputContainer} 
              value={search} 
              onChange={(e) => setSearch(e.target.value)} 
            />
            <div className={`${style.primaryScroll} space-y-2 max-h-48 overflow-y-auto pr-2 mt-2`}>
              {filteredMembers.map((member) => (
                <div key={member} className="flex justify-between items-center p-2 border-b border-white/5 hover:bg-white/5 rounded">
                  {member}
                  <button 
                    onClick={() => onAction("add", member)} 
                    className="text-[var(--primary)] cursor-pointer whitespace-nowrap"
                  >
                    + Invite
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}