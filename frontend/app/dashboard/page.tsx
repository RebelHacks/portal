"use client";
import style from "./dashboard.module.css";
import { useState } from "react";
import { redirect } from "next/navigation";
import { Submissions } from "../components/dashboard/Submissions";
import { Schedule } from "../components/dashboard/Schedule";
import { Team } from "../components/dashboard/Team";
import { Header } from "../components/dashboard/Header";

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

      < Header setIsDrawerOpen={setIsDrawerOpen} />

      <main className="flex-1 p-4 md:p-10">
        <div className="max-w-5xl mx-auto space-y-8">
          
          {activeTab === "Team" && ( <Team /> )}

          {activeTab === "Schedule" && ( <Schedule /> )}

          {activeTab === "Submissions" && ( <Submissions /> )}

        </div>
      </main>
    </div>
  );
}