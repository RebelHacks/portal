import { useState, useEffect, useCallback } from "react";
import style from "../../dashboard/dashboard.module.css";
import api from "@/lib/api";
import { useTeamContext } from "./TeamContext";
import type { Invitation } from "@/lib/types";

export function TeamInvitationsDash() {
  const { refresh } = useTeamContext();
  const [invitations, setInvitations] = useState<Invitation[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchInvitations = useCallback(async () => {
    try {
      const data = await api.get<Invitation[]>("/invitations");
      setInvitations(data);
    } catch (err) {
      console.error("Failed to fetch invitations:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchInvitations();
  }, [fetchInvitations]);

  const accept = async (id: number) => {
    try {
      await api.post(`/invitations/${id}/accept`);
      await refresh();
    } catch (err) {
      console.error("Failed to accept invitation:", err);
    }
  };

  const decline = async (id: number) => {
    try {
      await api.post(`/invitations/${id}/decline`);
      setInvitations((prev) => prev.filter((inv) => inv.id !== id));
    } catch (err) {
      console.error("Failed to decline invitation:", err);
    }
  };

  if (loading) return null;

  return (
    <div className={style.card}>
      <h2 className={style.primaryTitle}>Invitations</h2>

      {invitations.length === 0 ? (
        <p className="text-gray-400">You have no invitations at this time.</p>
      ) : (
        <div className="space-y-3 mt-4">
          {invitations.map((inv) => (
            <div
              key={inv.id}
              className="flex justify-between items-center bg-white/5 p-3 rounded-lg"
            >
              <span>{inv.teamName}</span>
              <div className="flex gap-2">
                <button
                  onClick={() => accept(inv.id)}
                  className={`${style.primaryButton} text-xs`}
                >
                  Accept
                </button>
                <button
                  onClick={() => decline(inv.id)}
                  className={`${style.warnButton} text-xs`}
                >
                  Decline
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}