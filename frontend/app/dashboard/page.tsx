"use client";
import style from "./dashboard.module.css";
import { useState } from "react";
import { redirect } from "next/navigation";
import { Submissions } from "../components/dashboard/Submissions";
import { Schedule } from "../components/dashboard/Schedule";
import { Team, TeamData} from "../components/dashboard/Team";
import { Header } from "../components/dashboard/Header";
import { Drawer } from "../components/dashboard/Drawer";

// !! If you want to test the dashboard before the event, set debug = true !!
const debug = false;
const INITIAL_MEMBERS = ["Sally", "Alice", "Bob", "Kate", "Fred", "Alex", "Noah", "Billy"];
const TEAM_LIMIT = 5;

export default function RebelHackPage() {
  // !! IMPORTANT !!
  // Anything before this date will redirect to /redirect
  // If you want to test the dashboard before the event, set debug = true
  const now = new Date();
  if (now < new Date("2026-02-02T12:00:00-08:00") && !debug) {
    redirect('/redirect');
  }
  const [activeTab, setActiveTab] = useState("Team");
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [team, setTeam] = useState<TeamData>({
    teamName: "",
    isTeamCreated: false,
    currentTeam: [{ name: "User" }],
    availableMembers: INITIAL_MEMBERS,
    teamLimit: TEAM_LIMIT
  });

  return (
    <div className={`${style.pageContainer} min-h-screen flex flex-col relative`}>
      <Header setIsDrawerOpen={setIsDrawerOpen} />
      <Drawer isDrawerOpen={isDrawerOpen} setIsDrawerOpen={setIsDrawerOpen} activeTab={activeTab} setActiveTab={setActiveTab} />
      <main className="flex-1 p-4 md:p-10">
        <div className="max-w-5xl mx-auto space-y-8">
          {activeTab === "Team" && (< Team teamData={team} setTeam={setTeam} />)}
          {activeTab === "Schedule" && (< Schedule />)}
          {activeTab === "Submissions" && (< Submissions />)}
        </div>
      </main>
    </div>
  );
}