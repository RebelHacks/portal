import style from "../../dashboard/dashboard.module.css";
import type { TeamData } from "./Team";

interface TeamInvitationsProps {
  // Prop 1: List of full TeamData objects acting as invites
  invitations: TeamData[]; 
  // Prop 2: Handler sends back the specific team object to overwrite state
  onAction: (action: "accept" | "decline", team: TeamData) => void;
}

export function TeamInvitationsDash({ invitations, onAction }: TeamInvitationsProps) {
  return (
    <div className={style.card}>
      <h2 className={style.primaryTitle}>Invitations</h2>
      
      {invitations.length === 0 ? (
        <p className="text-gray-400">You have no invitations at this time.</p>
      ) : (
        <div className="space-y-3">
          {invitations.map((team, index) => (
            <div key={index} className="flex flex-col md:flex-row justify-between items-center bg-white/5 p-4 rounded-lg gap-4">
              <div>
                <span className="font-bold text-[var(--primary)] text-lg">{team.teamName}</span>
              </div>
              
              <div className="flex gap-3 w-full md:w-auto">
                <button 
                  onClick={() => onAction("decline", team)} 
                  className={`${style.warnButton} text-xs flex-1 md:flex-none`}
                >
                  Decline
                </button>
                <button 
                  onClick={() => onAction("accept", team)} 
                  className={`${style.primaryButton} text-xs flex-1 md:flex-none`}
                >
                  Accept & Join
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}