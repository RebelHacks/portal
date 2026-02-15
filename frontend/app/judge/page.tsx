"use client";

import { useState } from "react";

type Team = {
  id: number;
  teamName: string;
  project: string;
  groupMembers?: string;
  review?: string;
  application?: number;
  technicality?: number;
  creativity?: number;
  functionality?: number;
  theme?: boolean;
  totalScore?: number;
};

export default function JudgeDashboard() {
  const [teams, setTeams] = useState<Team[]>([
    { id: 1, teamName: "Team 1", project: "Smart Campus App", groupMembers: "Alice, Bob" },
    { id: 2, teamName: "Team 2", project: "AI Study Buddy", groupMembers: "Charlie, Dave" },
    { id: 3, teamName: "Team 3", project: "Green Energy Tracker", groupMembers: "Eve, Frank" },
  ]);

  const [expandedTeam, setExpandedTeam] = useState<number | null>(null);

  const toggleExpanded = (index: number) => {
    setExpandedTeam((prev) => (prev === index ? null : index));
  };

  const handleScoreChange = (teamId: number, field: keyof Team, value: string | boolean) => {
    setTeams((prev) =>
      prev.map((t) => {
        if (t.id !== teamId) return t;

        const updated = { ...t, [field]: field === "review" ? value : typeof value === "string" ? Number(value) : value,};

        // Compute total score
        const total =
          (updated.application || 0) +
          (updated.technicality || 0) +
          (updated.creativity || 0) +
          (updated.functionality || 0) +
          (updated.theme ? 1 : 0); // Theme adds 1 point if checked

        updated.totalScore = total;

        return updated;
      })
    );
  };

  const submitReview = (teamId: number) => {
    const team = teams.find((t) => t.id === teamId);
    if (!team) return;

    alert(
      `Review submitted for ${team.teamName}!\n` +
        `Application: ${team.application || 0}\n` +
        `Technicality: ${team.technicality || 0}\n` +
        `Creativity: ${team.creativity || 0}\n` +
        `Functionality: ${team.functionality || 0}\n` +
        `Theme: ${team.theme ? "Yes" : "No"}\n` +
        `Total Score: ${team.totalScore || 0}\n` +
        `Review: ${team.review || ""}`
    );
  };

  return (
    <main className="min-h-screen bg-dark-blue-900 text-white p-6">
      <header className="mb-8 border-b-4 border-yellow-400 pb-4 text-center">
        <h1 className="text-3xl font-bold text-yellow-400">Judge Name</h1>
        <p className="opacity-90">Assigned Teams & Reviews</p>
      </header>

      <section className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 items-start">
        {teams.map((team, index) => (
          <div
            key={team.id}
            className="rounded-2xl border-2 border-yellow-400 bg-white/5 p-5 shadow-lg"
          >
            <div className="flex items-center justify-between mb-2">
              <h2 className="text-xl font-semibold text-yellow-400">{team.teamName}</h2>
              <button
                onClick={() => toggleExpanded(index)}
                className="text-yellow-400 hover:text-yellow-300 transition-colors"
              >
                {expandedTeam === index ? "▼" : "▶"}
              </button>
            </div>

            {expandedTeam === index && (
              <>
                <p className="font-medium mb-2">Project: {team.project}</p>
                <p className="mb-2">Members: {team.groupMembers}</p>

                <textarea
                  placeholder="Enter review..."
                  value={team.review || ""}
                  onChange={(e) => handleScoreChange(team.id, "review", e.target.value)}
                  className="w-full rounded-md p-2 text-white mb-2"
                />

                {/* Dropdowns for scores */}
                <div className="grid grid-cols-2 gap-2 mb-2">
                  {(["application", "technicality", "creativity", "functionality"] as const).map(
                    (field) => (
                      <div key={field}>
                        <label className="block text-sm mb-1 capitalize">{field}</label>
                        <select
                          value={team[field] || 0}
                          onChange={(e) => handleScoreChange(team.id, field, e.target.value)}
                          className="w-full rounded-md p-2 text-white"
                        >
                          {[0, 1, 2, 3, 4, 5].map((num) => (
                            <option key={num} value={num}>
                              {num}
                            </option>
                          ))}
                        </select>
                      </div>
                    )
                  )}
                </div>

                {/* Theme checkbox */}
                <div className="mb-2">
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={team.theme || false}
                      onChange={(e) => handleScoreChange(team.id, "theme", e.target.checked)}
                      className="rounded"
                    />
                    Theme
                  </label>
                </div>

                <p className="font-bold text-yellow-400 mb-2">
                  Total Score: {team.totalScore || 0}
                </p>

                <button
                  onClick={() => submitReview(team.id)}
                  className="bg-yellow-400 text-white font-bold px-4 py-2 rounded-md"
                >
                  Submit
                </button>
              </>
            )}
          </div>
        ))}
      </section>
    </main>
  );
}