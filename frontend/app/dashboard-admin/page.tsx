"use client";

import styles from "./admin.module.css";
import { Header } from "../components/dashboard-admin/Header-admin";
import { Drawer } from "../components/dashboard-admin/Drawer-admin";
import { useState } from "react";
import Teams from "../components/dashboard-admin/Teams";
import Arrivals from "../components/dashboard-admin/Arrivals";


export default function AdminPage() {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [activeTab, setActiveTab] = useState("Arrivals");


  return (
    <div
      className={`${styles.pageContainer} min-h-screen flex flex-col relative`}
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
          {activeTab === "Arrivals" && <Arrivals />}
          {activeTab === "Teams" && <Teams />}
        </div>
      </main>
    </div>
  );
}
