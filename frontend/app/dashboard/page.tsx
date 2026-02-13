"use client";
import style from "./dashboard.module.css";
import { useState } from "react";
import { Submissions } from "../components/dashboard/Submissions";
import { Schedule } from "../components/dashboard/Schedule";
import { Team } from "../components/dashboard/Team";
import { Header } from "../components/dashboard/Header";
import { Drawer } from "../components/dashboard/Drawer";

export default function RebelHackPage() {
  const [activeTab, setActiveTab] = useState("Team");
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  return (
    <div
      className={`${style.pageContainer} min-h-screen flex flex-col relative`}
    >
      <Header setIsDrawerOpen={setIsDrawerOpen} />
      <Drawer
        isDrawerOpen={isDrawerOpen}
        setIsDrawerOpen={setIsDrawerOpen}
        activeTab={activeTab}
        setActiveTab={setActiveTab}
      />
      <main className="flex-1 p-4 md:p-10">
        <div className="max-w-5xl mx-auto space-y-8">
          {activeTab === "Team" && <Team />}
          {activeTab === "Schedule" && <Schedule />}
          {activeTab === "Submissions" && <Submissions />}
        </div>
      </main>
    </div>
  );
}
