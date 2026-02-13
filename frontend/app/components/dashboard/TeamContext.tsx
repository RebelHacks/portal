'use client';

import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from 'react';
import api from '@/lib/api';
import { getJwtEmail } from '@/lib/auth';
import type { Team, User } from '@/lib/types';

interface TeamContextValue {
  loading: boolean;
  teamId: number | null;
  currentUserId: number | null;
  isLeader: boolean;
  refresh: () => Promise<void>;
}

const TeamContext = createContext<TeamContextValue>({
  loading: true,
  teamId: null,
  currentUserId: null,
  isLeader: false,
  refresh: async () => {},
});

export function useTeamContext() {
  return useContext(TeamContext);
}

export function TeamProvider({ children }: { children: ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [teamId, setTeamId] = useState<number | null>(null);
  const [currentUserId, setCurrentUserId] = useState<number | null>(null);
  const [isLeader, setIsLeader] = useState(false);

  const refresh = useCallback(async () => {
    const email = getJwtEmail();
    if (!email) { setLoading(false); return; }

    try {
      const [users, teams] = await Promise.all([
        api.get<User[]>('/users'),
        api.get<Team[]>('/teams'),
      ]);

      const me = users.find(u => u.email === email);
      if (me) setCurrentUserId(me.id);

      const myTeam = teams.find(t =>
        t.members?.some(m => m.email === email)
      ) ?? null;
      setTeamId(myTeam?.id ?? null);
      setIsLeader(me != null && myTeam != null && myTeam.leaderId === me.id);
    } catch (err) {
      console.error('Failed to check team status:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  return (
    <TeamContext.Provider value={{ loading, teamId, currentUserId, isLeader, refresh }}>
      {children}
    </TeamContext.Provider>
  );
}
