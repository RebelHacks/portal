import style from "../../dashboard/dashboard.module.css";
import { useState, useEffect, useCallback } from "react";
import api from "@/lib/api";
import { useTeamContext } from "./TeamContext";
import type { Team } from "@/lib/types";

export function TeamProjectDash() {
  const { teamId, currentUserId, isLeader } = useTeamContext();
  const [name, setName] = useState("");
  const [desc, setDesc] = useState("");
  const [isCreated, setIsCreated] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  const fetchProject = useCallback(async () => {
    try {
      const teams = await api.get<Team[]>('/teams');
      const team = teams.find(t => t.id === teamId);
      if (!team) return;

      setName(team.project.name);
      setDesc(team.project.details);
      setIsCreated(!!team.project.name);
    } catch (err) {
      console.error('Failed to fetch project:', err);
    } finally {
      setLoading(false);
    }
  }, [teamId, currentUserId]);

  useEffect(() => { fetchProject(); }, [fetchProject]);

  const handleSave = async () => {
    if (!name || !desc) return;
    setSaving(true);
    try {
      await api.patch(`/teams/${teamId}`, { projectName: name, projectDetails: desc });
      setIsCreated(true);
      setIsEditing(false);
      await fetchProject();
    } catch (err: any) {
      console.error('Failed to save project:', err);
    } finally {
      setSaving(false);
    }
  };

  if (loading) return null;

  // Mock Data
  const rounds = [
    { id: 1, score: 4.2, feedback: "Solid concept. Focus on the core user loop for next round." },
    { id: 2, score: 4.8, feedback: "Exceptional improvement on the UI/UX. The flow is very intuitive." },
  ];

  // 3. Logic Helpers
  const isMemberWaiting = !isLeader && !isCreated;
  const isFormView = isLeader && (!isCreated || isEditing);
  const isProjectView = isCreated && (!isEditing || !isLeader);

  return (
    <div className={style.card}>
      <div className={style.splitPanel}>
        
        {/* --- LEFT COLUMN --- */}
        <div className="flex-[1.2] flex flex-col gap-6">
          
          {/* SCENARIO 1: MEMBER WAITING */}
          {isMemberWaiting && (
            <div className="space-y-6">
              {/* Header Section: Matches the "Created" view layout */}
              <div className="flex justify-between items-center border-b border-[var(--primary-light-border)] pb-4">
                <h2 className={`${style.primaryTitle} mb-0`}>
                  Pending Creation
                </h2>
              </div>
                
              {/* Content Section: Aligned to the left under the header */}
              <div className="pt-2">
                <p className="text-gray-400 max-w-md leading-relaxed">
                  Your Team Leader hasn't created the project yet. Once they set the scope, 
                  the details and judge feedback will appear here.
                </p>
              </div>
            </div>
          )}

          {/* SCENARIO 2 & 3: HEADER (Shared for Form & View) */}
          {!isMemberWaiting && (
            <div className="flex justify-between items-center border-b border-[var(--primary-light-border)] pb-4">
              <h2 className={`${style.primaryTitle} mb-0`}>
                {isFormView ? (isCreated ? "Edit Details" : "Project Management") : name}
              </h2>
              
              {isCreated && isLeader && (
                <button 
                  onClick={() => setIsEditing(!isEditing)}
                  className={`${style.secondaryButton} text-xs px-3`}
                >
                  {isEditing ? "CANCEL" : "EDIT"}
                </button>
              )}
            </div>
          )}

          {/* SCENARIO 2: FORM VIEW (Create/Edit) */}
          {isFormView && (
            <div className="space-y-6">
              <div>
                <label className="text-[var(--primary)] text-xs font-bold tracking-widest block mb-2">
                  PROJECT NAME
                </label>
                <input
                  type="text"
                  className={style.inputContainer}
                  placeholder="Enter project name..."
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                />
              </div>

              <div>
                <label className="text-[var(--primary)] text-xs font-bold tracking-widest block mb-2">
                  DESCRIPTION
                </label>
                <textarea
                  className={`${style.descriptionInput} ${style.primaryScroll} min-h-[150px]`}
                  placeholder="Briefly describe the goals..."
                  maxLength={250}
                  value={desc}
                  onChange={(e) => setDesc(e.target.value)}
                />
              </div>

              <button 
                onClick={handleSave}
                className={style.primaryButton}
                disabled={saving}
              >
                {saving ? 'Saving...' : isCreated ? "Save Changes" : "Create Project"}
              </button>
            </div>
          )}

          {/* SCENARIO 3: PROJECT READ-ONLY VIEW */}
          {isProjectView && (
            <div>
              <label className="text-[var(--primary)] text-xs font-bold tracking-widest block mb-2">
                OVERVIEW
              </label>
              <p className="text-gray-300 leading-relaxed whitespace-pre-wrap">
                {desc}
              </p>
            </div>
          )}
        </div>

        {/* --- RIGHT COLUMN --- */}
        <div className={style.splitRight}>
          
          {/* GUIDELINES (Show during Form View) */}
          {(!isCreated || isEditing) && (
            <>
              <h4 className={style.secondaryTitle}>Guidelines</h4>
              <ul className={`${style.list} text-gray-400 leading-loose`}>
                <li>Maximum 250 characters.</li>
                <li>Scores are on a 5.0 scale.</li>
                <li>Feedback released after every round.</li>
              </ul>
            </>
          )}

          {/* FEEDBACK (Show during Project View) */}
          {isCreated && !isEditing && (
            <>
              <h4 className={style.secondaryTitle}>Judge Feedback</h4>
              
              <div className="flex flex-col gap-6 mt-6">
                {rounds.map((round) => (
                  <div key={round.id}>
                    <div className="flex justify-between items-baseline mb-2">
                      <span className="text-[var(--primary)] text-xs font-bold tracking-widest">
                        ROUND {round.id}
                      </span>
                      <span className="text-white font-bold text-lg">
                        {round.score} <span className="text-gray-400 text-xs font-normal">/ 5.0</span>
                      </span>
                    </div>
                    
                    <div className={style.feedbackBox}>
                      <p className="text-gray-300 text-sm leading-relaxed m-0">
                        {round.feedback}
                      </p>
                    </div>
                  </div>
                ))}

                <div className="opacity-50 pl-4">
                  <span className="text-gray-400 text-xs font-bold">
                    Round {rounds.length + 1} Pending...
                  </span>
                </div>
              </div>
            </>
          )}
        </div>

      </div>
    </div>
  );
}