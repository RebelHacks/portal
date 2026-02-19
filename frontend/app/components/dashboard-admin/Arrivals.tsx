"use client";

import { useMemo, useState } from "react";
import styles from "../../dashboard-admin/admin.module.css";
import { useApi } from "@/hooks/useApi";
import api from "@/lib/api";
import type { ArrivalState, User } from "@/lib/types";

export default function Arrivals() {
  const { data, refetch } = useApi<User[]>("/admin/users");
  const userData = useMemo(() => data ?? [], [data]);

  // Update the state when searching and filtering
  const [arrivalSearch, setArrivalSearch] = useState("");
  const [arrivalFilter, setArrivalFilter] = useState<"All" | ArrivalState>("All");

  // Handle arrival state change
  const handleSetArrivalState = async (userId: number, newState: ArrivalState) => {
    try {
      await api.patch(`/admin/users/${userId}`, { state: newState });
      await refetch();
    } catch (error) {
      console.error("Error updating arrival state:", error);
    }
  };

  // Calculate stats
  const arrivalCount = useMemo(() => {
    const checkedIn = userData.filter((u) => u.state === "Checked In").length;
    const pending = userData.filter((u) => u.state === "Pending").length;
    return { checkedIn, pending };
  }, [userData]);

  // Filter users based on search and filter
  const filteredUser = useMemo(() => {
    const q = arrivalSearch.trim().toLowerCase();

    return userData
      .filter((u) => {
        if (!q) return true;
        return (
          u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.team.toLowerCase().includes(q)
        );
      })
      .filter((u) => {
        if (arrivalFilter === "All") return true;
        return u.state === arrivalFilter;
      });
  }, [userData, arrivalSearch, arrivalFilter]);

  return (
    <>
      <h2 className={styles.primaryTitle}>Arrivals</h2>
      <div className={styles.card}>
        <div className="flex flex-row gap-2 md:flex-row md:items-center">
          <input
            value={arrivalSearch}
            onChange={(e) => setArrivalSearch(e.target.value)}
            className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm text-(--sub-text)"
            placeholder="Search name, email, team"
          />

          <select
            value={arrivalFilter}
            onChange={(e) => setArrivalFilter(e.target.value as "All" | ArrivalState)}
            className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm text-(--sub-text)">
            <option value="All">All</option>
            <option value="Pending">Pending</option>
            <option value="Checked In">Checked In</option>
          </select>

          <div className="flex flex-row ml-auto gap-2 text-(--sub-text)">
            <div className="rounded-xl bg-[#111435] border border-[#868585] px-3 py-2 text-sm font-bold">
              Pending: {arrivalCount.pending}
            </div>
            <div className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm font-bold">
              Checked In: {arrivalCount.checkedIn}
            </div>
          </div>
        </div>
      </div>

      <div className={styles.card}>
        <div className="overflow-x-auto h-100">
          {/* Members Table */}
          <table className="w-full text-sm">
            {/* Headeres */}
            <thead className="text-(--card-header)">
              <tr className="border-b">
                <th className="py-3 text-left font-medium">User</th>
                <th className="py-3 text-left font-medium">Team</th>
                <th className="py-3 text-left font-medium">State</th>
                <th className="py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>

            <tbody>
              {filteredUser.map((u) => {
                const pill =
                  u.state === "Checked In"
                    ? "border-green-400/30 bg-green-500/10 text-green-300"
                    : "border-white/10 bg-white/5 text-white/70";

                return (
                  <tr key={u.id} className="border-b last:border-b-0 text-(--sub-text)">
                    <td className="py-3">
                      <div className="font-medium">{u.name}</div>
                      <div className="text-xs opacity-60">{u.email}</div>
                    </td>
                    <td className="py-3">{u.team}</td>
                    <td className="py-3">
                      <span
                        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${pill}`}>
                        {u.state}
                      </span>
                    </td>

                    <td className="py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <select
                          value={u.state}
                          onChange={(e) => handleSetArrivalState(u.id, e.target.value as ArrivalState)}
                          className="rounded-lg bg-[#111435] border border-[#FEA70A] px-2 py-1 text-xs disabled:opacity-60 text-(--sub-text)">
                          <option value="Pending">Pending</option>
                          <option value="Checked In">Checked In</option>
                        </select>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {filteredUser.length === 0 && (
                <tr>
                  <td className="py-6 text-center opacity-70 text-(--sub-text)" colSpan={4}>
                    No matching users.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
