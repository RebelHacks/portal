"use client";

import { useMemo, useState } from "react";
import styles from "../../dashboard-admin/admin.module.css";
import useApi from "@/hooks/useApi";

type ArrivalState = "Not Arrived" | "Arrived" | "Checked In";

type Person = {
  id: string;
  name: string;
  email: string;
  team: string;
  track: "Software" | "Hardware";
  state: ArrivalState;
};

// CRUD - Create, Read, Update, Delete

// (Create) GET    - Gets information (Make a get request to an endpoint )
// (Read)   POST   - Modify the state of underlying data, (create a new resource)
// (Update) PUT    - Update the existing data (Used for arrival state)
// (Delete) DELETE - Removes information 

export default function Arrivals() {
  //  Mock person
  const [people, setPeople] = useState<Person[]>([
    {
      id: "p1",
      name: "Ava Nguyen",
      email: "ava.nguyen@unlv.edu",
      team: "Neon Ninjas",
      track: "Software",
      state: "Not Arrived",
    },
    {
      id: "p2",
      name: "Mateo Rivera",
      email: "mateo.rivera@unlv.edu",
      team: "Circuit Cowboys",
      track: "Hardware",
      state: "Not Arrived",
    },
    {
      id: "p3",
      name: "Sofia Patel",
      email: "sofia.patel@unlv.edu",
      team: "Desert Debuggers",
      track: "Software",
      state: "Not Arrived",
    },
    {
      id: "p4",
      name: "Jordan Lee",
      email: "jordan.lee@csn.edu",
      team: "Robo Rebels",
      track: "Hardware",
      state: "Not Arrived",
    },
    {
      id: "p5",
      name: "Liam Chen",
      email: "liam.chen@unlv.edu",
      team: "Pixel Pioneers",
      track: "Software",
      state: "Not Arrived",
    },
    {
      id: "p6",
      name: "Maria Gonzalez",
      email: "maria.gonzalez@csn.edu",
      team: "Signal Squad",
      track: "Hardware",
      state: "Not Arrived",
    },
    {
      id: "p7",
      name: "Noah Brooks",
      email: "noah.brooks@unlv.edu",
      team: "Byte Benders",
      track: "Software",
      state: "Not Arrived",
    },
    {
      id: "p8",
      name: "Aaliyah Johnson",
      email: "aaliyah.johnson@unlv.edu",
      team: "Desert Coders",
      track: "Software",
      state: "Not Arrived",
    },
    {
      id: "p9",
      name: "Ethan Park",
      email: "ethan.park@csn.edu",
      team: "Hardware Heroes",
      track: "Hardware",
      state: "Not Arrived",
    },
    {
      id: "p10",
      name: "Priya Shah",
      email: "priya.shah@unlv.edu",
      team: "Quantum Quokkas",
      track: "Software",
      state: "Not Arrived",
    },
  ]);

  const [arrivalSearch, setArrivalSearch] = useState("");
  const [arrivalFilter, setArrivalFilter] = useState<"All" | ArrivalState>(
    "All",
  );

  const arrivalsStats = useMemo(() => {
    const totalPeople = people.length;
    const arrived = people.filter((p) => p.state === "Arrived").length;
    const checkedIn = people.filter((p) => p.state === "Checked In").length;
    const notArrived = people.filter((p) => p.state === "Not Arrived").length;
    return { totalPeople, arrived, checkedIn, notArrived };
  }, [people]);

  const filteredPeople = useMemo(() => {
    const q = arrivalSearch.trim().toLowerCase();

    return people
      .filter((p) => {
        if (!q) return true;
        return (
          p.name.toLowerCase().includes(q) ||
          p.email.toLowerCase().includes(q) ||
          p.team.toLowerCase().includes(q)
        );
      })
      .filter((p) => {
        if (arrivalFilter === "All") return true;

        return p.state === arrivalFilter;
      });
  }, [people, arrivalSearch, arrivalFilter]);

  function updatePersonState(personId: string, newState: ArrivalState) {
    setPeople((prev) =>
      prev.map((p) => (p.id === personId ? { ...p, state: newState } : p)),
    );
  }

  return (
    <>
      {/* Arrivals List */}
      <h2 className={styles.primaryTitle}>Arrivals</h2>
      <div className="grid gap-3 sm:grid-cols-3">
        <div className={styles.card}>
          <div className="text-sm opacity-70">Not Arrived</div>
          <div className="mt-1 text-2xl font-semibold">
            {arrivalsStats.notArrived}
          </div>
        </div>
        <div className={styles.card}>
          <div className="text-sm opacity-70">Arrived</div>
          <div className="mt-1 text-2xl font-semibold">
            {arrivalsStats.arrived}
          </div>
        </div>
        <div className={styles.card}>
          <div className="text-sm opacity-70">Checked In</div>
          <div className="mt-1 text-2xl font-semibold">
            {arrivalsStats.checkedIn}
          </div>
        </div>
      </div>
      <div className={styles.card}>
        <div className="flex flex-col gap-2 md:flex-row md:items-center">
          <input
            value={arrivalSearch}
            onChange={(e) => setArrivalSearch(e.target.value)}
            className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm"
            placeholder="Search name, email, team"
          />

          {/* Filter People */}
          <select
            value={arrivalFilter}
            onChange={(e) =>
              setArrivalFilter(e.target.value as typeof arrivalFilter)
            }
            className="rounded-xl bg-[#111435] border border-[#FEA70A] px-3 py-2 text-sm"
          >
            <option value="All">All</option>
            <option value="Not Arrived">Not Arrived</option>
            <option value="Arrived">Arrived</option>
            <option value="Checked In">Checked In</option>
          </select>
        </div>

        {/* People list/table */}
        <div className="overflow-x-auto h-100">
          <table className="w-full text-sm">
            <thead className="opacity-70">
              <tr className="border-b">
                <th className="py-3 text-left font-medium">Person</th>
                <th className="py-3 text-left font-medium">Team</th>
                <th className="py-3 text-left font-medium">Track</th>
                <th className="py-3 text-left font-medium">State</th>
                <th className="py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>

            <tbody>
              {filteredPeople.map((p) => {
                const pill =
                  p.state === "Checked In"
                    ? "border-green-400/30 bg-green-500/10 text-green-300"
                    : p.state === "Arrived"
                      ? "border-yellow-400/30 bg-yellow-500/10 text-yellow-300"
                      : "border-white/10 bg-white/5 text-white/70";

                return (
                  <tr key={p.id} className="border-b last:border-b-0">
                    <td className="py-3">
                      <div className="font-medium">{p.name}</div>
                      <div className="text-xs opacity-60">{p.email}</div>
                    </td>
                    <td className="py-3">{p.team}</td>
                    <td className="py-3">{p.track}</td>
                    <td className="py-3">
                      <span
                        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${pill}`}
                      >
                        {p.state}
                      </span>
                    </td>

                    <td className="py-3 text-right">
                      <div className="flex justify-end gap-2">
                        {/* Set state */}
                        <select
                          value={p.state}
                          onChange={(e) =>
                            updatePersonState(
                              p.id,
                              e.target.value as ArrivalState,
                            )
                          }
                          className="rounded-lg bg-[#111435] border border-[#FEA70A]  px-2 py-1 text-xs"
                        >
                          <option value="Not Arrived">Not Arrived</option>
                          <option value="Arrived">Arrived</option>
                          <option value="Checked In">Checked In</option>
                        </select>

                        <button className="rounded-lg border border-[#FEA70A] bg-[#111435] px-3 py-1.5 text-xs hover:opacity-80">
                          View
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {filteredPeople.length === 0 && (
                <tr>
                  <td className="py-6 text-center opacity-70" colSpan={5}>
                    No matching people.
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
