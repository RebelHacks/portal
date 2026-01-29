import style from "../../dashboard/dashboard.module.css";
import type { TeamData } from "./Team";

interface TeamCreationDashProps {
  teamData: TeamData;
  onAction: (action: "updateName" | "create", value?: string) => void;
}

export function TeamCreationDash({ teamData, onAction }: TeamCreationDashProps) {
  const { teamName, teamLimit } = teamData;

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
                onChange={(e) => onAction("updateName", e.target.value)}
                placeholder="Enter team name..."
              />
            </div>
            <button 
              onClick={() => onAction("create")} 
              className={style.primaryButton}
            >
              Create Team
            </button>
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

      
    </>
  );
}